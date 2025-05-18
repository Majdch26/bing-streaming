/* Loading screen */
window.addEventListener('load', function() {
    setTimeout(function() {
        document.querySelector('.loading-screen').style.opacity = '0';
        setTimeout(function() {
            document.querySelector('.loading-screen').style.display = 'none';
        }, 500);
    }, 1000);
});

/* Section scrolling */
function scrollSection(section, amount) {
    const container = section.querySelector('.movies-container');
    container.scrollBy({ left: amount, behavior: 'smooth' });
}

/* Watchlist popup toggle */
function toggleWatchlistPopup() {
    document.getElementById('watchlistPopup').classList.toggle('active');
}

/* Carousel functionality */
let currentSlide = 0;
const slides = document.querySelectorAll('.hero-slide');
const dots = document.querySelectorAll('.dot');
/*const videos = document.querySelectorAll('.trailer-video');*/
let slideInterval;

function showSlide(n) {
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    /*videos.forEach(video => video.pause());*/
    
    currentSlide = (n + slides.length) % slides.length;
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
    /*videos[currentSlide].play().catch(e => console.log("Autoplay prevented"));*/
    
    clearInterval(slideInterval);
    slideInterval = setInterval(nextSlide, 8000);
}

function nextSlide() { showSlide(currentSlide + 1); }
function prevSlide() { showSlide(currentSlide - 1); }
function goToSlide(n) { showSlide(n); }

/* Initialize carousel */
if (slides.length > 0) {
    videos[0].play().catch(e => console.log("Autoplay prevented"));
    if (slides.length > 1) {
        slideInterval = setInterval(nextSlide, 8000);
    }
}