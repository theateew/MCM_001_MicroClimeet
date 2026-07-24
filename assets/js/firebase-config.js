/**
 * Firebase Realtime Database Configuration - MicroClimeet
 * Replace the placeholder values with your Firebase Project Configuration.
 */

const firebaseConfig = {
    apiKey: "YOUR_API_KEY",
    authDomain: "YOUR_AUTH_DOMAIN",
    databaseURL: "YOUR_DATABASE_URL", // Required for Realtime Database
    projectId: "YOUR_PROJECT_ID",
    storageBucket: "YOUR_STORAGE_BUCKET",
    messagingSenderId: "YOUR_MESSAGING_SENDER_ID",
    appId: "YOUR_APP_ID"
};

let firebaseActive = false;
let telemetryRef = null;

// Helper to check if credentials are still placeholders
function isFirebaseConfigured() {
    return firebaseConfig.apiKey && 
           firebaseConfig.apiKey !== "YOUR_API_KEY" && 
           firebaseConfig.databaseURL && 
           firebaseConfig.databaseURL !== "YOUR_DATABASE_URL";
}

if (isFirebaseConfigured()) {
    try {
        // Initialize Firebase App
        firebase.initializeApp(firebaseConfig);
        // Get reference to Realtime Database
        const database = firebase.database();
        // Path to your IoT device telemetry node (e.g. "devices/ESP32_GH_1/telemetry")
        telemetryRef = database.ref("telemetry");
        firebaseActive = true;
        console.log("Firebase Realtime Database successfully initialized.");
    } catch (error) {
        console.error("Error initializing Firebase Realtime Database:", error);
    }
} else {
    console.warn("Firebase not configured. Defaulting to local simulator mode.");
}
