function showSection(id){

    document.querySelectorAll('.section').forEach(sec=>{
        sec.classList.remove('active');
    });

    document.getElementById(id).classList.add('active');
}

function bookSession(e){
    e.preventDefault();
    alert("Session Booked!");
}

function logout(){
    alert("Logged out!");
    window.location.href = "login.html";
}