const sliders = document.querySelectorAll(".hero-slider");

sliders.forEach(slider => {

  const slides = slider.querySelectorAll(".slide");
  let index = 0;

  setInterval(() => {

    slides[index].classList.remove("active");

    index++;

    if(index >= slides.length){
      index = 0;
    }

    slides[index].classList.add("active");

  }, 4000);

});

// ⚙️ JS (SINCRONIZADO + PAUSA PRO PARA CARSD
const cards = document.querySelectorAll('.cat-card');

let index = 0;
let intervalo;

// iniciar slider
function iniciarSlider(){

  intervalo = setInterval(() => {

    cards.forEach(card => {

      const images = card.querySelectorAll('.cat-slider img');
      const text = card.querySelector('.cat-text');
      const textos = card.querySelector('.cat-slider')
                          .dataset.texts.split(',');

      images[index].classList.remove('active');

      let next = (index + 1) % images.length;

      images[next].classList.add('active');

      text.textContent = textos[next];

    });

    index = (index + 1) % 3;

  }, 2500);

}

// detener slider
function detenerSlider(){
  clearInterval(intervalo);
}

// eventos (desktop)
cards.forEach(card => {
  card.addEventListener('mouseenter', detenerSlider);
  card.addEventListener('mouseleave', iniciarSlider);
});

// eventos (mobile)
cards.forEach(card => {
  card.addEventListener('touchstart', detenerSlider);
  card.addEventListener('touchend', iniciarSlider);
});

// iniciar al cargar
iniciarSlider();

