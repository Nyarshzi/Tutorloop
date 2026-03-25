let isOn = false;

const lamp = document.getElementById("lampWrapper");

function toggleLight(){
    isOn = !isOn;

    const body = document.body;
    const box = document.getElementById("loginBox");

    if(isOn){
        body.classList.add("light-on");
        box.classList.add("active");

        lamp.classList.remove("center");
        lamp.classList.add("move");

    }else{
        body.classList.remove("light-on");
        box.classList.remove("active");

        lamp.classList.remove("move");
        lamp.classList.add("center");
    }
}

/* SHOW PASSWORD */
function togglePassword(){
    const pass = document.getElementById("password");
    pass.type = pass.type === "password" ? "text" : "password";
}

/* LOGIN */
function login(){

    const email = document.querySelector('input[type="email"]').value;
    const password = document.getElementById("password").value;

    // SAMPLE ACCOUNT
    const correctEmail = "admin@tutorloop.com";
    const correctPassword = "123456";

    if(email === correctEmail && password === correctPassword){
        alert("Login Successful!");
        
        // redirect example (optional)
        // window.location.href = "dashboard.html";

    } else {
        alert("Invalid Email or Password!");
    }
}