// firebase-config.js

// Your web app's Firebase configuration
const firebaseConfig = {
    apiKey: "AIzaSyDAJEo_JfDA-C6pprRdrWL2H1JSZtA-4yM",
    authDomain: "clover-outfit-rentals-a314a.firebaseapp.com",
    projectId: "clover-outfit-rentals-a314a",
    storageBucket: "clover-outfit-rentals-a314a.firebasestorage.app",
    messagingSenderId: "389486115860",
    appId: "1:389486115860:web:b45fb520745e6debab7b01",
    measurementId: "G-M6W9FCRGBH"
};

// Initialize Firebase
try {
    if (!firebase.apps.length) {
        firebase.initializeApp(firebaseConfig);
    }

    // Initialize Auth globally
    window.auth = firebase.auth();
    
    // Set persistence
    window.auth.setPersistence(firebase.auth.Auth.Persistence.LOCAL)
        .catch(error => {
            console.error("Auth persistence error:", error);
        });

    console.log("Firebase initialized successfully");
} catch (error) {
    console.error("Firebase initialization error:", error);
    document.getElementById('firebaseError').style.display = 'block';
}

// Initialize auth
window.auth.useDeviceLanguage();

// The handleGoogleAuth function
async function handleGoogleAuth(isSignUp = false) {
    const button = isSignUp ? 
        document.getElementById('googleSignUpBtn') : 
        document.getElementById('googleSignInBtn');
    
    try {
        button.style.opacity = '0.7';
        button.style.pointerEvents = 'none';
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';

        // Create Google provider with specific parameters
        const provider = new firebase.auth.GoogleAuthProvider();
        
        // Add these settings to force account selection
        provider.setCustomParameters({
            prompt: 'select_account',  // Forces account selection every time
            access_type: 'offline'     // Enables refresh token
        });

        // Sign out first to ensure the account picker shows up
        await window.auth.signOut();

        // Use signInWithPopup with the modified provider
        const result = await window.auth.signInWithPopup(provider);
        const user = result.user;

        if (!user) {
            throw new Error('No user data available');
        }

        const userData = {
            name: user.displayName,
            email: user.email,
            uid: user.uid,
            imageUrl: user.photoURL
        };

        const response = await fetch('handle_google_auth.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify(userData)
        });

        let responseText = await response.text();
        responseText = responseText.replace(/<[^>]*>/g, '').trim();
        
        console.log('Cleaned response:', responseText);
        
        let data;
        try {
            data = JSON.parse(responseText);
        } catch (e) {
            console.error('Parse error:', e);
            console.error('Response text:', responseText);
            throw new Error('Invalid server response');
        }

        if (data.success) {
            window.location.href = data.redirect || 'index.php';
        } else {
            throw new Error(data.message || 'Authentication failed');
        }

    } catch (error) {
        console.error('Authentication error:', error);
        let errorMessage = 'Authentication failed: ';

        switch (error.code) {
            case 'auth/popup-closed-by-user':
                errorMessage = 'Sign-in window was closed. Please try again.';
                break;
            case 'auth/network-request-failed':
                errorMessage = 'Network error. Please check your internet connection.';
                break;
            case 'auth/popup-blocked':
                errorMessage = 'Pop-up was blocked. Please allow pop-ups for this site.';
                break;
            default:
                errorMessage += error.message;
        }

        alert(errorMessage);

    } finally {
        button.style.opacity = '1';
        button.style.pointerEvents = 'auto';
        button.innerHTML = isSignUp ? 
            '<div class="google-icon-wrapper"><img class="google-icon" src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg"/></div><p class="btn-text">Sign up with Google</p>' :
            '<div class="google-icon-wrapper"><img class="google-icon" src="https://upload.wikimedia.org/wikipedia/commons/5/53/Google_%22G%22_Logo.svg"/></div><p class="btn-text">Sign in with Google</p>';
    }
}
