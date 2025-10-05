const inputs = document.querySelectorAll(".input");


function addcl(){
	let parent = this.parentNode.parentNode;
	parent.classList.add("focus");
}

function remcl(){
	let parent = this.parentNode.parentNode;
	if(this.value == ""){
		parent.classList.remove("focus");
	}
}


inputs.forEach(input => {
	input.addEventListener("focus", addcl);
	input.addEventListener("blur", remcl);
});

var contador = true;
function vistas() {
    var texto = document.getElementById("verPassword");
    if (contador == true) {
        texto.className = "fas fa-eye-slash verPassword";
        document.getElementById("input").type="text";
        contador=false;
    } else {
        texto.className = "fas fa-eye verPassword";
        document.getElementById("input").type="password";
        contador = true;
    }
}