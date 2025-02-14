document.addEventListener('DOMContentLoaded', function () {
    const registerForm = document.getElementById('register-form');
    const registerError = document.getElementById('register-error');
    const registerSuccess = document.getElementById('register-success');
    registerForm.addEventListener('submit', function (e) {
        e.preventDefault();
        const email = document.getElementById('email').value;
        const password = document.getElementById('password').value;
        const passwordConfirm = document.getElementById('confirm-password').value;
 
        if(password !== passwordConfirm){
            registerError.style.display = 'block';
            registerSuccess.style.display = 'none';
        }else{
            registerError.style.display = 'none';
            registerSuccess.style.display = 'block';
            setTimeout(function(){
                window.location.href = "index.html";
            },3000)
            registerForm.reset();
        }
    })
});