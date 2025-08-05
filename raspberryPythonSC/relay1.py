import RPi.GPIO as GPIO
import time
import threading
from datetime import datetime
from flask import Flask
import logging
from flask import Flask, request, jsonify
import re
import requests
from flask import Flask, Response
from picamera2 import Picamera2
import cv2
import requests
from gtts import gTTS
import os
import tempfile
import ssl
import logging
import threading
from typing import Optional
import smtplib
from email.mime.multipart import MIMEMultipart
from email.mime.text import MIMEText
from email.mime.base import MIMEBase
from email import encoders
import pyaudio
import wave
import face_recognition
import numpy as np
from scipy.spatial import distance as dist
from flask import Flask, Response, render_template
import random
import subprocess

# Initialize Picamera2
picam2 = Picamera2()
config = picam2.preview_configuration  # Corrected: Removed parentheses
picam2.configure(config)
picam2.start()

# GPIO setup
GPIO.setmode(GPIO.BCM)
REGISTER_BUTTON_PIN = 11  # GPIO pin for face registration button
VERIFY_BUTTON_PIN = 6    # GPIO pin for face verification button
LOCK_PIN = 24            # GPIO pin for door lock solenoid
BUZZER_PIN = 4          # GPIO pin for the buzzer

# Set up GPIO pins
GPIO.setup(REGISTER_BUTTON_PIN, GPIO.IN, pull_up_down=GPIO.PUD_UP)
GPIO.setup(VERIFY_BUTTON_PIN, GPIO.IN, pull_up_down=GPIO.PUD_UP)
GPIO.setup(LOCK_PIN, GPIO.OUT)
GPIO.setup(BUZZER_PIN, GPIO.OUT)
GPIO.output(LOCK_PIN, GPIO.HIGH)  # Initialize lock in the locked state
GPIO.output(BUZZER_PIN, GPIO.LOW)  # Initialize buzzer in the off state


# Initialize Flask app
app = Flask(__name__)

# Set up logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger("RaspberryPiControl")

# Set up GPIO mode and pin definitions
GPIO.setmode(GPIO.BCM)
LOCK_PIN = 24
LIGHT_PIN = 12
BUTTON_PIN_1 = 6
BUTTON_PIN_2 = 5
BUTTON_PIN_3 = 16
BUTTON_PIN_4 = 20
FAN = 25
O_LIGHT_PIN = 9
O_BUTTON = 10

# Stepper motor GPIO pins
IN1, IN2, IN3, IN4 = 17, 18, 27, 22
step_sequence = [
    [1, 0, 0, 1],
    [1, 0, 0, 0],
    [1, 1, 0, 0],
    [0, 1, 0, 0],
    [0, 1, 1, 0],
    [0, 0, 1, 0],
    [0, 0, 1, 1],
    [0, 0, 0, 1],
]
STEPS_PER_ROTATION = 1000

# States
relay_2_state = True
fan_state = True
fan_state1 = True
# Add a global flag for buzzer activation
buzzer_activated = False


class NotificationState:
    def __init__(self):
        self.current_notification = "System ready"
        self.lock = threading.Lock()

state = NotificationState()

def speak(message):
    """Text-to-speech function with online and offline fallback."""
    try:
        # Try using gTTS for online TTS
        tts = gTTS(text=message, lang='en')
        with tempfile.NamedTemporaryFile(delete=True, suffix='.mp3') as fp:
            tts.save(fp.name)
            os.system(f"mpg321 {fp.name}")
    except Exception as e:
        logger.warning(f"gTTS failed, switching to offline TTS: {str(e)}")
        try:
            # Fallback to Pico TTS for offline TTS
            temp_file = "output.wav"
            os.system(f'pico2wave -w {temp_file} "{message}" && aplay {temp_file} && rm {temp_file}')
        except Exception as offline_error:
            logger.error(f"Offline TTS failed: {str(offline_error)}")


# Logger setup
logger = logging.getLogger("Voice Command Logger")
logger.setLevel(logging.INFO)
            
def generate():
    while True:
        # Capture frame from PiCamera2
        frame = picam2.capture_array()
        # Convert the frame to JPEG format for streaming
        ret, jpeg = cv2.imencode('.jpg', frame)
        if not ret:
            break
        # Yield frame as a byte stream for HTTP response
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + jpeg.tobytes() + b'\r\n\r\n')

def update_notification(message, access_denied=False):
    """Update the current notification with timestamp."""
    with state.lock:
        current_time = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
        state.current_notification = f"{message}"
        if access_denied:
            state.notifications.append(state.current_notification)
        logger.info(f"Updated notification: {state.current_notification}")

def setup():
    # Define GPIO pins for buttons in a dictionary for clarity
    PINS = {
        "BUTTON_1": BUTTON_PIN_1,
        "BUTTON_2": BUTTON_PIN_2,
        "BUTTON_3": BUTTON_PIN_3,
        "BUTTON_4": BUTTON_PIN_4,
        "BUTTON_5": O_BUTTON
    }

    # Configure other output pins
    GPIO.setup(LOCK_PIN, GPIO.OUT)
    GPIO.setup(LIGHT_PIN, GPIO.OUT)
    GPIO.setup(O_LIGHT_PIN, GPIO.OUT)
    GPIO.setup(FAN, GPIO.OUT)
    GPIO.setup(IN1, GPIO.OUT)
    GPIO.setup(IN2, GPIO.OUT)
    GPIO.setup(IN3, GPIO.OUT)
    GPIO.setup(IN4, GPIO.OUT)

    # Configure button pins as output and set them to HIGH
    for button, pin in PINS.items():
        GPIO.setup(pin, GPIO.OUT)  # Configure as output
        GPIO.output(pin, GPIO.HIGH)  # Set initial state to HIGH

    # Initialize other output pins to LOW
    GPIO.output(LOCK_PIN, GPIO.HIGH)
    GPIO.output(LIGHT_PIN, GPIO.LOW)
    GPIO.output(O_LIGHT_PIN, GPIO.LOW)
    GPIO.output(FAN, GPIO.HIGH)
    GPIO.output(IN1, GPIO.LOW)
    GPIO.output(IN2, GPIO.LOW)
    GPIO.output(IN3, GPIO.LOW)
    GPIO.output(IN4, GPIO.LOW)
    
    
# Directory to save encoded faces
if not os.path.exists("saved_faces"):
    os.makedirs("saved_faces")

# Constants for blink detection
EYE_AR_THRESH = 0.25  # Eye aspect ratio threshold for blink detection
EYE_AR_CONSEC_FRAMES = 3  # Number of consecutive frames to detect a blink

# Constants for mouth detection
MOUTH_AR_THRESH = 0.75  # Mouth aspect ratio threshold for mouth open detection

# Initialize counters for liveness detection
blink_counter = 0
blink_detected = False
mouth_open_detected = False
head_movement_detected = False

def get_next_face_id():
    """Get the next available face ID (1, 2, 3, ...)."""
    existing_files = os.listdir("saved_faces")
    if not existing_files:
        return 1
    existing_ids = [int(f.split(".")[0]) for f in existing_files if f.endswith(".npy")]
    return max(existing_ids) + 1

def eye_aspect_ratio(eye):
    """Calculate the eye aspect ratio (EAR) to detect blinks."""
    A = dist.euclidean(eye[1], eye[5])
    B = dist.euclidean(eye[2], eye[4])
    C = dist.euclidean(eye[0], eye[3])
    ear = (A + B) / (2.0 * C)
    return ear

def mouth_aspect_ratio(mouth):
    """Calculate the mouth aspect ratio (MAR) to detect mouth open."""
    A = dist.euclidean(mouth[1], mouth[7])
    B = dist.euclidean(mouth[2], mouth[6])
    C = dist.euclidean(mouth[3], mouth[5])
    D = dist.euclidean(mouth[0], mouth[4])
    mar = (A + B + C) / (2.0 * D)
    return mar

def capture_and_save_face():
    print("Position yourself in front of the camera. Capturing face in 3 seconds...")
    speak("Please, Position yourself in front of the camera for registering your face. Capturing face in 3 seconds")
    time.sleep(3)  # Give the user 3 seconds to position themselves
    
    # Capture frame
    frame = picam2.capture_array()
    rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

    # Detect faces
    face_locations = face_recognition.face_locations(rgb_frame)

    if len(face_locations) == 0:
        print("No face detected. Please try again.")
        speak("No face detected. Please try again.")
        
        return

    # Get the first detected face
    face_encoding = face_recognition.face_encodings(rgb_frame, face_locations)[0]

    # Load saved face encodings
    known_face_encodings = []
    known_face_ids = []
    for file in os.listdir("saved_faces"):
        if file.endswith(".npy"):
            face_id = os.path.splitext(file)[0]
            face_encoding_saved = np.load(f"saved_faces/{file}")
            known_face_encodings.append(face_encoding_saved)
            known_face_ids.append(face_id)

    # Check if the face is already registered
    matches = face_recognition.compare_faces(known_face_encodings, face_encoding)
    if True in matches:
        first_match_index = matches.index(True)
        face_id = known_face_ids[first_match_index]
        print(f"Face already registered as {face_id}. Updating face encoding.")
        update_notification("Face registration successful")
        speak("face already registered, updating face encoding")
    else:
        face_id = get_next_face_id()
        print(f"New face registered as {face_id}.")
        update_notification("Face registration successful")
        speak("face registered")

    # Save or update the face encoding
    np.save(f"saved_faces/{face_id}.npy", face_encoding)
    print(f"Face saved as {face_id}.npy")

def verify_face():
    global blink_counter, blink_detected, mouth_open_detected, head_movement_detected
    print("Position yourself in front of the camera for verification. Please perform liveness checks.")
    known_face_encodings = []
    known_face_ids = []
    speak("Please, Position yourself in front of the camera for verification")

    # Load saved face encodings
    for file in os.listdir("saved_faces"):
        if file.endswith(".npy"):
            face_id = os.path.splitext(file)[0]
            face_encoding = np.load(f"saved_faces/{file}")
            known_face_encodings.append(face_encoding)
            known_face_ids.append(face_id)

    if not known_face_encodings:
        print("No faces registered. Please register a face first.")
        return

    start_time = time.time()
    while time.time() - start_time < 25:  # Allow 25 seconds for verification
        # Capture frame
        frame = picam2.capture_array()
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

        # Detect faces
        face_locations = face_recognition.face_locations(rgb_frame)
        face_encodings = face_recognition.face_encodings(rgb_frame, face_locations)

        for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
            # Compare the detected face with known faces
            matches = face_recognition.compare_faces(known_face_encodings, face_encoding)
            face_id = "Unknown"

            if True in matches:
                first_match_index = matches.index(True)
                face_id = known_face_ids[first_match_index]

                # Detect facial landmarks for liveness checks
                landmarks = face_recognition.face_landmarks(rgb_frame, [(top, right, bottom, left)])
                if landmarks:
                    landmarks = landmarks[0]
                    left_eye = landmarks["left_eye"]
                    right_eye = landmarks["right_eye"]
                    mouth = landmarks["top_lip"] + landmarks["bottom_lip"]

                    # Calculate eye aspect ratio (EAR) for blink detection
                    left_ear = eye_aspect_ratio(left_eye)
                    right_ear = eye_aspect_ratio(right_eye)
                    ear = (left_ear + right_ear) / 2.0

                    # Calculate mouth aspect ratio (MAR) for mouth open detection
                    mar = mouth_aspect_ratio(mouth)

                    # Check for blink
                    if ear < EYE_AR_THRESH:
                        blink_counter += 1
                    else:
                        if blink_counter >= EYE_AR_CONSEC_FRAMES:
                            blink_detected = True
                        blink_counter = 0

                    # Check for mouth open
                    if mar > MOUTH_AR_THRESH:
                        mouth_open_detected = True

                    # Draw landmarks and EAR/MAR on the frame
                    for eye in (left_eye, right_eye):
                        for point in eye:
                            cv2.circle(frame, point, 2, (0, 255, 0), -1)
                    for point in mouth:
                        cv2.circle(frame, point, 2, (0, 0, 255), -1)
                    cv2.putText(frame, f"EAR: {ear:.2f}", (left, top - 30), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 255, 0), 2)
                    cv2.putText(frame, f"MAR: {mar:.2f}", (left, top - 60), cv2.FONT_HERSHEY_SIMPLEX, 0.7, (0, 0, 255), 2)

                # If all liveness checks pass, unlock the door
                if blink_detected and mouth_open_detected:
                    print(f"Face recognized as {face_id}. Opening door lock.")
                    GPIO.output(LOCK_PIN, GPIO.LOW)  # Unlock the door
                    update_notification("Door Opened")
                    speak("door opened")
                    time.sleep(5)  # Keep the door unlocked for 5 seconds
                    GPIO.output(LOCK_PIN, GPIO.HIGH)  # Lock the door again
                    update_notification("Door Locked")
                    speak("door locked")
                    blink_detected = False
                    mouth_open_detected = False
                    return
            else:
                # If the face is unknown, sound the buzzer
                print("Unknown face detected. Sounding the buzzer.")
                update_notification("Intruder Alert, Access Denied")
                speak("intruder alert, access denied")
                GPIO.output(BUZZER_PIN, GPIO.HIGH)  # Turn on the buzzer
                time.sleep(8)  # Keep the buzzer on for 8 seconds
                GPIO.output(BUZZER_PIN, GPIO.LOW)  # Turn off the buzzer

            # Draw a rectangle around the detected face
            cv2.rectangle(frame, (left, top), (right, bottom), (0, 255, 0), 2)
            cv2.putText(frame, face_id, (left, top - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.75, (0, 255, 0), 2)

        # Show the camera preview
        cv2.imshow("Face Verification", frame)

        if cv2.waitKey(1) & 0xFF == ord('q'):
            break

    cv2.destroyAllWindows()
    print("Verification failed. Liveness checks not passed.")
    update_notification("Face verification failed")
    speak("NO face detected access denied")

def generate_frames():
    while True:
        frame = picam2.capture_array()
        rgb_frame = cv2.cvtColor(frame, cv2.COLOR_BGR2RGB)

        # Detect faces
        face_locations = face_recognition.face_locations(rgb_frame)
        face_encodings = face_recognition.face_encodings(rgb_frame, face_locations)

        for (top, right, bottom, left), face_encoding in zip(face_locations, face_encodings):
            # Draw a rectangle around the detected face
            cv2.rectangle(frame, (left, top), (right, bottom), (0, 255, 0), 2)
            cv2.putText(frame, "Face Detected", (left, top - 10), cv2.FONT_HERSHEY_SIMPLEX, 0.75, (0, 255, 0), 2)

        ret, buffer = cv2.imencode('.jpg', frame)
        frame = buffer.tobytes()
        yield (b'--frame\r\n'
               b'Content-Type: image/jpeg\r\n\r\n' + frame + b'\r\n')


def set_pins(pins):
    """Set the state of motor control pins."""
    GPIO.output(IN1, GPIO.HIGH if pins[0] else GPIO.LOW)
    GPIO.output(IN2, GPIO.HIGH if pins[1] else GPIO.LOW)
    GPIO.output(IN3, GPIO.HIGH if pins[2] else GPIO.LOW)
    GPIO.output(IN4, GPIO.HIGH if pins[3] else GPIO.LOW)


def rotate_motor(steps, delay, direction="cw"):
    """Rotate the motor a specific number of steps."""
    if direction == "ccw":
        sequence = step_sequence[::-1]  # Reverse sequence for counter-clockwise
    else:
        sequence = step_sequence

    for step in range(steps):
        for pins in sequence:
            set_pins(pins)
            time.sleep(delay)
            
def open_light():
    global fan_state
    if fan_state:
        print("Turning on light...")
        GPIO.output(LIGHT_PIN, GPIO.HIGH)  # Activate relay
        update_notification("Indoor Lights On")
        speak("indoor lights opened")
        time.sleep(1)  # Delay before turning on
    else:
        print("Turning off light...")
        GPIO.output(LIGHT_PIN, GPIO.LOW)  # Deactivate relay
        update_notification("Indoor Lights Off")
        speak("indoor lights off")
        time.sleep(1)  # Delay before turning off
    
    fan_state = not fan_state  # Toggle the state
    
    
def O_open_light():
    global fan_state1
    if fan_state1:
        print("Turning on light...")
        GPIO.output(O_LIGHT_PIN, GPIO.HIGH)  # Activate relay
        update_notification("Outdoor Lights On")
        speak("outdoor lights on")
        time.sleep(1)  # Delay before turning on
    else:
        print("Turning off light...")
        GPIO.output(O_LIGHT_PIN, GPIO.LOW)  # Deactivate relay
        update_notification("Outdoor Lights Off")
        speak("outdoor lights off")
        time.sleep(1)  # Delay before turning off
    
    fan_state1 = not fan_state1  # Toggle the state


def open_fan():
    """Toggles the state of the second relay with a delay."""
    global relay_2_state
    if relay_2_state:
        print("Turning off fan...")
        update_notification("Fan Open")
        GPIO.output(FAN, GPIO.LOW)  # Activate relay
        speak("Fan open")
        time.sleep(1)  # Delay before turning on
    else:
        print("Turning on fan...")
        update_notification("Fan Off")
        GPIO.output(FAN, GPIO.HIGH)  # Deactivate relay
        speak("Fan off")
        time.sleep(1)  # Delay before turning off
    
    relay_2_state = not relay_2_state  # Toggle the state

def handle_stepper_motor():
    """Handle the motor movement when the button is pressed."""
    print("Button pressed! Rotating motor once...")
    # Rotate 360 degrees (200 steps per rotation)
    update_notification("Garage Open")
    speak("opening garage ")
    rotate_motor(STEPS_PER_ROTATION, 0.001, direction="cw")
    print("Waiting for 3 seconds before returning...")
    time.sleep(3)  # Delay before returning
    
    print("Rotating back to start position...")
    # Rotate back to the starting position
    update_notification("Garage closing")
    speak("closing garage")
    rotate_motor(STEPS_PER_ROTATION, 0.002, direction="ccw")
    print("Done!")

def main_loop():
    logger.info("Waiting for button presses...")
    while True:
        if GPIO.input(BUTTON_PIN_4) == GPIO.LOW:
            open_light()
        if GPIO.input(O_BUTTON) == GPIO.LOW:
            O_open_light()
        if GPIO.input(BUTTON_PIN_2) == GPIO.LOW:
            open_fan()
        if GPIO.input(BUTTON_PIN_3) == GPIO.LOW:
            handle_stepper_motor()      
        if GPIO.input(REGISTER_BUTTON_PIN) == GPIO.LOW:
            capture_and_save_face()
            time.sleep(1)  # Debounce delay
        if GPIO.input(VERIFY_BUTTON_PIN) == GPIO.LOW:
            verify_face()
            time.sleep(1)  # Debounce delay
        time.sleep(0.1)

@app.route('/get_notification', methods=['GET'])
def get_notification():
    """Endpoint to get current notification and handle alert status based on buzzer activation."""
    global buzzer_activated

    try:
        with state.lock:
            # Retrieve the current notification from state
            notification = state.current_notification

            # Check if the buzzer is activated, indicating an alert
            if buzzer_activated:
                # Override notification for an alert and start 10-second reset timer
                notification = "Three failed attempts detected!"
                threading.Timer(10, reset_buzzer_status).start()
                status = "alert"
            else:
                status = "success"

        return jsonify({
            "status": status,
            "notification": notification,
            "timestamp": datetime.now().isoformat()
        }), 200

    except Exception as e:
        logger.error(f"Error getting notification: {str(e)}")
        return jsonify({
            "status": "error",
            "message": "Internal server error"
        }), 500

@app.route('/control_solenoid', methods=['POST'])
def control_solenoid():
    try:
        # Try to get form data
        if request.form:
            switch_state = request.form.get("switch")
        else:
            # If form data is not present, return an error
            return jsonify({"status": "error", "message": "No form data provided"}), 400

        if switch_state == "on":
            # Activate solenoid
            GPIO.output(LOCK_PIN, GPIO.LOW)  # Activate solenoid
            speak("Door Opened")
            update_notification("Door Opened")
            return jsonify({"status": "success", "message": "Solenoid activated"}), 200
        elif switch_state == "off":
            # Deactivate solenoid
            GPIO.output(LOCK_PIN, GPIO.HIGH)
            speak("Door Locked")
            update_notification("Door Locked")
            return jsonify({"status": "success", "message": "Solenoid deactivated"}), 200
        else:
            return jsonify({"status": "error", "message": "Invalid switch state"}), 400

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500
    
@app.route('/control_fan', methods=['POST'])
def control_fan():
    try:
        # Try to get form data
        if request.form:
            switch_state = request.form.get("switch2")
        else:
            # If form data is not present, return an error
            return jsonify({"status": "error", "message": "No form data provided"}), 400

        if switch_state == "on":
            # Activate fan
            GPIO.output(FAN, GPIO.LOW)  # Activate fan
            speak("Fan Open")
            update_notification("Fan Open")
            return jsonify({"status": "success", "message": "fan activated"}), 200
        elif switch_state == "off":
            # Deactivate fan
            GPIO.output(FAN, GPIO.HIGH)
            speak("Fan Off")
            update_notification("Fan Off")
            return jsonify({"status": "success", "message": "fan deactivated"}), 200
        else:
            return jsonify({"status": "error", "message": "Invalid switch state"}), 400

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500
    
@app.route('/control_lights', methods=['POST'])
def control_lights():
    try:
        # Try to get form data
        if request.form:
            switch_state = request.form.get("switch3")
        else:
            # If form data is not present, return an error
            return jsonify({"status": "error", "message": "No form data provided"}), 400

        if switch_state == "on":
            # Activate lights
            GPIO.output(LIGHT_PIN, GPIO.HIGH)  # Activate lights
            speak("Indoor Lights On")
            update_notification("Indoor Lights On")
            return jsonify({"status": "success", "message": "lights activated"}), 200
        elif switch_state == "off":
            # Deactivate lights
            GPIO.output(LIGHT_PIN, GPIO.LOW)
            speak("Indoor Lights Off")
            update_notification("Indoor Lights Off")
            return jsonify({"status": "success", "message": "lights deactivated"}), 200
        else:
            return jsonify({"status": "error", "message": "Invalid switch state"}), 400

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500
    
@app.route('/control_lights2', methods=['POST'])
def control_lights2():
    try:
        # Try to get form data
        if request.form:
            switch_state = request.form.get("switch5")
        else:
            # If form data is not present, return an error
            return jsonify({"status": "error", "message": "No form data provided"}), 400

        if switch_state == "on":
            # Activate lights
            GPIO.output(O_LIGHT_PIN, GPIO.HIGH)  # Activate lights
            speak("Outdoor Lights On")
            update_notification("Outdoor Lights On")
            return jsonify({"status": "success", "message": "lights activated"}), 200
        elif switch_state == "off":
            # Deactivate lights
            GPIO.output(O_LIGHT_PIN, GPIO.LOW)
            speak("Outdoor Lights Off")
            update_notification("Outdoor Lights Off")
            return jsonify({"status": "success", "message": "lights deactivated"}), 200
        else:
            return jsonify({"status": "error", "message": "Invalid switch state"}), 400

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

@app.route('/control_garage', methods=['POST'])
def control_garage():
    try:
        # Try to get form data
        if request.form:
            switch_state = request.form.get("switch4")
        else:
            # If form data is not present, return an error
            return jsonify({"status": "error", "message": "No form data provided"}), 400

        if switch_state == "on":
            # Activate garage
            speak("Opening Garage")
            update_notification("Garage Open")
            rotate_motor(STEPS_PER_ROTATION, 0.001, direction="cw")# Activate garage
            return jsonify({"status": "success", "message": "garage activated"}), 200
        elif switch_state == "off":
            # Deactivate garage
            speak("Closing Garage")
            update_notification("Garage Closing")
            rotate_motor(STEPS_PER_ROTATION, 0.002, direction="ccw")
            return jsonify({"status": "success", "message": "garage deactivated"}), 200
        else:
            return jsonify({"status": "error", "message": "Invalid switch state"}), 400

    except Exception as e:
        return jsonify({"status": "error", "message": str(e)}), 500

@app.route('/video_feed')
def video_feed():
    return Response(generate(), mimetype='multipart/x-mixed-replace; boundary=frame')

@app.route('/')
def index():
    return 'Video Stream Running!'



class UserManager:
    def __init__(self, passwords_file_path):
        """
        Initialize the UserManager with a file path for passwords
        
        Args:
            passwords_file_path (str): Path to the passwords file
        """
        self.passwords_file_path = "/home/clnv/mitpasswords.txt"
        self.hardcoded_ids = {"051123", "admin"}  # Hardcoded IDs
        self.passwords = self.load_passwords()
    
    def load_passwords(self):
        """
        Load passwords from the specified file
        
        Returns:
            dict: Dictionary of user IDs and their passwords
        """
        passwords = {}
        if os.path.exists(self.passwords_file_path):  # Corrected here
            with open(self.passwords_file_path, "r") as file:
                for line in file:
                    user_id, password = line.strip().split("=")
                    passwords[user_id] = password
        else:
            logging.warning(f"Passwords file not found: {self.passwords_file_path}")
        
        return passwords


    def verify_password(self, user_id, password):
        """
        Verify user password
        
        Args:
            user_id (str): User identifier
            password (str): User password
        
        Returns:
            dict: Authentication result
        """
        # Check if user ID is hardcoded
        if user_id not in self.hardcoded_ids:
            return {
                "status": "error",
                "message": "User ID not found"
            }
        
        # Verify password
        if self.passwords.get(user_id) == password:
            return {
                "status": "success",
                "message": "Authentication successful"
            }
        
        return {
            "status": "error",
            "message": "Invalid password"
        }

# Initialize UserManager with the file path to the passwords file
PASSWORDS_FILE_PATH = "/home/clnv/mitpasswords.txt"  # Replace with the actual file path
user_manager = UserManager(PASSWORDS_FILE_PATH)

@app.route('/verify_credentials', methods=['POST'])
def verify_password():
    global current_recipient_email
    """
    Verify user password and authenticate
    """
    try:
        user_id = request.form.get("id")
        password = request.form.get("password")
        recipient_email = request.form.get("recipient_email")
        
        if not all([user_id, password]):
            return jsonify({
                "status": "error", 
                "message": "ID and password must be provided"
            }), 400
        
        result = user_manager.verify_password(user_id, password)
        
        # Validate email
        email_regex = r'^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+$'
        if not re.match(email_regex, recipient_email):
            return jsonify({"status": "error", "message": "Invalid recipient email format"}), 401
        
        if result["status"] == "success":
            current_recipient_email = recipient_email
            save_recipient_email(current_recipient_email)  # Save email for later use
            logging.info(f"User {user_id} authenticated successfully")
            return jsonify(result), 200
        else:
            logging.warning(f"Authentication failed for user {user_id}")
            return jsonify(result), 402
    
    except Exception as e:
        logging.error(f"Verification error: {e}")
        return jsonify({
            "status": "error", 
            "message": "Verification failed"
        }), 500

# Define the function to send an email with the new password
def send_new_password_email(sender_email, app_password, recipient_email, new_password):
    """
    Send the new password to the recipient's email.
    
    Args:
        sender_email (str): Sender's email address
        app_password (str): App password for authentication
        recipient_email (str): Recipient's email address
        new_password (str): New password that was set
    """
    
    try:
        # Create the email message
        msg = MIMEMultipart()
        msg['From'] = sender_email
        msg['To'] = recipient_email
        msg['Subject'] = "Password Change Notification"
        
        # Email body content
        body = f"Your password has been successfully changed. Your new password is: {new_password}"
        msg.attach(MIMEText(body, 'plain'))
        
        # Send the email using SMTP
        with smtplib.SMTP('smtp.gmail.com', 587) as server:
            server.starttls()
            server.login(sender_email, app_password)
            server.send_message(msg)
        
        logging.info(f"New password sent to {recipient_email}")
    except Exception as e:
        logging.error(f"Error sending email: {e}")


@app.route('/change_password', methods=['POST'])
def change_password():
    """
    Allows a user to change their password permanently.
    """
    try:
        user_id = request.form.get("id")
        current_password = request.form.get("current_password")
        new_password = request.form.get("new_password")
        
        # Load the recipient email from the function
        recipient_email = load_recipient_email()  # Use the function to load the email
        
        # Check if user ID, current password, and new password are provided
        if user_id is None or current_password is None or new_password is None:
            return jsonify({"status": "error", "message": "ID, current password, and new password must be provided"}), 400
        
        # Check if new password is blank
        if not new_password.strip():
            return jsonify({"status": "error", "message": "New password cannot be blank"}), 401
        
        # Check if user ID and current password are correct
        if user_manager.passwords.get(user_id) == current_password:
            # Update password in memory
            user_manager.passwords[user_id] = new_password
            # Persist changes to the file
            with open(user_manager.passwords_file_path, "w") as file:
                for uid, pwd in user_manager.passwords.items():
                    file.write(f"{uid}={pwd}\n")
            
            # Send the new password to the recipient email
            sender_email = "joshuacajimatvigilia@gmail.com"
            app_password = "utfo zekm yket vxsa"  # Consider using environment variables
            send_new_password_email(sender_email, app_password, recipient_email, new_password)
            
            logging.info(f"Password updated successfully for user {user_id}.")
            return jsonify({"status": "success", "message": "Password changed successfully"}), 200
        else:
            logging.warning(f"Password change failed for user {user_id}: Incorrect current password.")
            return jsonify({"status": "error", "message": "Invalid ID or current password"}), 402
    except Exception as e:
        logging.error(f"Error during password change: {e}")
        return jsonify({"status": "error", "message": str(e)}), 500
    
    # Global variable for recipient email
current_recipient_email = None

# Function to save the recipient email to a file
def save_recipient_email(email):
    """Save the recipient email to a file."""
    try:
        with open("recipient_email.txt", "w") as file:
            file.write(email)
        logger.info("Recipient email saved successfully.")
    except Exception as e:
        logger.error(f"Error saving recipient email: {str(e)}")

# Function to load the recipient email from a file
def load_recipient_email():
    """Load the recipient email from a file."""
    try:
        if os.path.exists("recipient_email.txt"):
            with open("recipient_email.txt", "r") as file:
                return file.read().strip()
        else:
            logger.warning("No recipient email found.")
            return None
    except Exception as e:
        logger.error(f"Error loading recipient email: {str(e)}")
        return None


@app.route('/get_recipient_email', methods=['GET'])
def get_recipient_email():
    """Retrieve the saved recipient email."""
    global current_recipient_email

    # Load the recipient email if not already loaded
    if current_recipient_email is None:
        current_recipient_email = load_recipient_email()

    if current_recipient_email:
        return jsonify({"status": "success", "recipient_email": current_recipient_email}), 200
    else:
        return jsonify({"status": "error", "message": "No recipient email found"}), 404

def send_email_with_attachment(sender_email, app_password, recipient_email, subject, body, image_path=None):
    """
    Send an email with an optional attachment using a simple, robust method.
    
    Args:
        sender_email (str): Sender's email address
        app_password (str): App password for authentication
        recipient_email (str): Recipient's email address
        subject (str): Email subject
        body (str): Email body text
        image_path (str, optional): Path to image to be attached
    """
    try:
        # Create a secure SSL context
        context = ssl.create_default_context()
        
        # Create the email message
        msg = MIMEMultipart()
        msg['From'] = sender_email
        msg['To'] = recipient_email
        msg['Subject'] = subject
        
        # Attach body text
        msg.attach(MIMEText(body, 'plain'))
        
        # Attach image if exists
        if image_path and os.path.exists(image_path):
            try:
                with open(image_path, 'rb') as attachment:
                    part = MIMEBase('application', 'octet-stream')
                    part.set_payload(attachment.read())
                
                encoders.encode_base64(part)
                part.add_header('Content-Disposition', f"attachment; filename= intruder_capture.jpg")
                msg.attach(part)
            except Exception as attach_error:
                logging.error(f"Error attaching image: {attach_error}")
        
        # Attempt to send email with multiple connection strategies
        for port in [587, 465]:  # Try both TLS and SSL ports
            try:
                if port == 587:
                    # TLS connection
                    with smtplib.SMTP('smtp.gmail.com', port, timeout=10) as server:
                        server.ehlo()
                        server.starttls(context=context)
                        server.login(sender_email, app_password)
                        server.send_message(msg)
                else:
                    # SSL connection
                    with smtplib.SMTP_SSL('smtp.gmail.com', port, context=context, timeout=10) as server:
                        server.login(sender_email, app_password)
                        server.send_message(msg)
                
                logging.info(f"Email sent successfully via port {port}")
                return True
            
            except Exception as conn_error:
                logging.warning(f"Email send attempt via port {port} failed: {conn_error}")
        
        # If all attempts fail
        raise Exception("All email sending attempts failed")
    
    except Exception as e:
        logging.error(f"Final email sending error: {str(e)}")
        return False

def threaded_email_send(sender_email, app_password, recipient_email, subject, body, image_path=None):
    """
    Send email in a separate thread to prevent blocking
    """
    email_thread = threading.Thread(
        target=send_email_with_attachment,
        args=(
            sender_email, 
            app_password, 
            recipient_email, 
            subject, 
            body, 
            image_path
        )
    )
    email_thread.start()


# To track total energy consumption in watt-hours
total_energy = 0

def get_rpi_voltage():
    try:
        output = subprocess.check_output(["vcgencmd", "measure_volts"], universal_newlines=True)
        voltage = float(output.split("=")[1].replace("V", "").strip())
        return voltage
    except Exception as e:
        print(f"❌ Error reading voltage: {e}")
        return None

def estimate_power():
    global total_energy

    voltage = get_rpi_voltage()
    if voltage is None:
        return {"error": "Voltage read error"}

    current = random.uniform(0.6, 1.2)
    power = voltage * current
    energy_in_wh = power / 3600
    total_energy += energy_in_wh  

    return {
        "current_power": round(power, 2),
        "voltage": round(voltage, 3),
        "current": round(current, 3),
        "total_energy": round(total_energy, 4)
    }

@app.route('/get_energy', methods=['GET'])
def get_energy():
    energy_data = estimate_power()
    return jsonify(energy_data)    
    
if __name__ == '__main__':
    try:
        setup()
        threading.Thread(
            target=lambda: app.run(host='0.0.0.0', port=5000, debug=False),
            daemon=True
        ).start()
        main_loop()
    except KeyboardInterrupt:
        logger.info("Program terminated.")
    finally:
        GPIO.cleanup()
        
