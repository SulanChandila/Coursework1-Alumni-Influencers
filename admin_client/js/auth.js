document.addEventListener("DOMContentLoaded", () => {
    const loginForm = document.getElementById("loginForm");
    const errorMessage = document.getElementById("error-message");

    //Base URL of CodeIgniter API
    const API_BASE_URL = "http://localhost/alumini_api_cw/index.php";

    //Handling login form submit
    loginForm.addEventListener("submit", async (e) => {
        e.preventDefault();

        //Getting entered email and password
        const email = document.getElementById("email").value;
        const password = document.getElementById("password").value;

        try {
            //Sending login request to API
            const response = await fetch(`${API_BASE_URL}/auth/login`, {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({ email: email, password: password })
            });

            const data = await response.json();

            if (response.ok) {
                //Saving JWT token in session
                sessionStorage.setItem("jwt_token", data.token);

                //Redirecting to dashboard
                window.location.href = "index.html";
            } else {
                //Showing login error message
                errorMessage.textContent = data.error || "Login failed.";
                errorMessage.classList.remove("d-none");
            }
        } catch (error) {
            console.error("Error:", error);

            //Showing server connection error
            errorMessage.textContent = "Cannot connect to the server.";
            errorMessage.classList.remove("d-none");
        }
    });
});