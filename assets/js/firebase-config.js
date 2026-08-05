/**
 * Firebase Realtime Database Configuration - MicroClimeet
 * Replace the placeholder values with your Firebase Project Configuration.
 */

const firebaseConfig = {
    apiKey: "AIzaSyAt_WIKnzhPRX_rU2u9MTcMAhjjT4FFkJ8",
    authDomain: "microclimeet-7eff0.firebaseapp.com",
    databaseURL: "https://microclimeet-7eff0-default-rtdb.asia-southeast1.firebasedatabase.app",
    projectId: "microclimeet-7eff0",
    storageBucket: "microclimeet-7eff0.firebasestorage.app",
    messagingSenderId: "878725329807",
    appId: "1:878725329807:web:765bcc80a7b5904f69d1fa",
    measurementId: "G-EZ3QLWJMTX"
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
