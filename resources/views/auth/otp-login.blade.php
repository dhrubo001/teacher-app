<!DOCTYPE html>
<html>

<head>
    <title>OTP Login Test</title>

    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        body {
            font-family: Arial;
            padding: 40px;
        }

        input,
        button {
            padding: 10px;
            margin: 5px 0;
            width: 250px;
        }
    </style>
</head>

<body>

    <h2>Firebase OTP Login (Test)</h2>

    <input type="text" id="phone" placeholder="+91XXXXXXXXXX">
    <div id="recaptcha-container"></div>

    <button onclick="sendOtp()">Send OTP</button>

    <br><br>

    <input type="text" id="otp" placeholder="Enter OTP">
    <button onclick="verifyOtp()">Verify OTP</button>

    <p id="status"></p>

    <!-- ✅ Firebase v10 MODULAR SDK -->
    <script type="module">
        /* =========================
           Import Firebase Modules
        ========================= */
        import {
            initializeApp
        } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-app.js";
        import {
            getAuth,
            signInWithPhoneNumber,
            RecaptchaVerifier
        } from "https://www.gstatic.com/firebasejs/10.7.1/firebase-auth.js";

        /* =========================
           Firebase Configuration
        ========================= */
        const firebaseConfig = {
            apiKey: "AIzaSyAyT563HTfYyraudq1BBXOcqCJLv8nx3w0",
            authDomain: "schoolhomeworknotification.firebaseapp.com",
            projectId: "schoolhomeworknotification",
        };

        /* =========================
           Initialize Firebase
        ========================= */
        const app = initializeApp(firebaseConfig);
        const auth = getAuth(app);

        /* =========================
           Globals
        ========================= */
        let confirmationResult = null;

        /* =========================
           Initialize reCAPTCHA
        ========================= */
        window.recaptchaVerifier = new RecaptchaVerifier(
            auth,
            'recaptcha-container', {
                size: 'invisible'
            }
        );

        /* =========================
           Send OTP
        ========================= */
        window.sendOtp = function() {
            const phone = document.getElementById('phone').value;

            if (!phone) {
                document.getElementById('status').innerText = 'Enter phone number';
                return;
            }

            signInWithPhoneNumber(auth, phone, window.recaptchaVerifier)
                .then(result => {
                    confirmationResult = result;
                    document.getElementById('status').innerText = 'OTP sent';
                })
                .catch(error => {
                    document.getElementById('status').innerText = error.message;
                });
        };

        /* =========================
           Verify OTP
        ========================= */
        window.verifyOtp = function() {
            const otp = document.getElementById('otp').value;

            if (!confirmationResult) {
                document.getElementById('status').innerText = 'Please send OTP first';
                return;
            }

            confirmationResult.confirm(otp)
                .then(result => result.user.getIdToken())
                .then(idToken => {
                    return fetch('/verify-otp', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document
                                .querySelector('meta[name="csrf-token"]').content
                        },
                        body: JSON.stringify({
                            idToken
                        })
                    });
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = data.redirect;
                    } else {
                        document.getElementById('status').innerText = data.message;
                    }
                })
                .catch(() => {
                    document.getElementById('status').innerText = 'Invalid OTP';
                });
        };
    </script>

</body>

</html>
