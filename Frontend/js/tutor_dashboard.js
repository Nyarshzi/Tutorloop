function showSection(id){

    document.querySelectorAll('.section').forEach(sec=>{
        sec.classList.remove('active');
    });

    document.getElementById(id).classList.add('active');
}

/* ACTION BUTTONS */
function accept(){
    alert("Session Accepted!");
}

function decline(){
    alert("Session Declined!");
}

function logout(){
    alert("Logged out!");
    window.location.href = "login.html";
}