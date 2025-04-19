document.addEventListener('DOMContentLoaded', function() {
    // Create butterfly elements
    createButterflies();
    
    // Create gas bubbles
    createGasBubbles();
    
    // Animate form elements
    animateFormElements();
});

function createButterflies() {
    const container = document.querySelector('.butterfly-container');
    const butterflyCount = 15;
    
    for (let i = 0; i < butterflyCount; i++) {
        const butterfly = document.createElement('div');
        butterfly.classList.add('butterfly');
        
        // Random position
        butterfly.style.left = `${Math.random() * 100}vw`;
        butterfly.style.top = `${Math.random() * 100}vh`;
        
        // Random size
        const size = Math.random() * 20 + 20;
        butterfly.style.width = `${size}px`;
        butterfly.style.height = `${size}px`;
        
        // Random color
        const hue = Math.floor(Math.random() * 360);
        butterfly.style.filter = `hue-rotate(${hue}deg)`;
        
        // Random animation duration and delay
        const duration = Math.random() * 10 + 10;
        const delay = Math.random() * 5;
        butterfly.style.animationDuration = `${duration}s`;
        butterfly.style.animationDelay = `${delay}s`;
        
        container.appendChild(butterfly);
    }
}

function createGasBubbles() {
    const container = document.body;
    const bubbleCount = 20;
    
    setInterval(() => {
        const bubble = document.createElement('div');
        bubble.classList.add('gas-bubble');
        
        // Random position
        const posX = Math.random() * window.innerWidth;
        bubble.style.left = `${posX}px`;
        bubble.style.bottom = '0';
        
        // Random size
        const size = Math.random() * 100 + 50;
        bubble.style.width = `${size}px`;
        bubble.style.height = `${size}px`;
        
        // Random animation duration
        const duration = Math.random() * 5 + 5;
        bubble.style.animationDuration = `${duration}s`;
        
        container.appendChild(bubble);
        
        // Remove bubble after animation completes
        setTimeout(() => {
            bubble.remove();
        }, duration * 1000);
    }, 500);
}

function animateFormElements() {
    const formElements = document.querySelectorAll('.form-group');
    
    formElements.forEach((element, index) => {
        element.classList.add('form-appear');
        element.style.animationDelay = `${index * 0.1}s`;
    });
}

// Function to create colorful butterflies
function createColorfulButterflies() {
    const container = document.querySelector('.butterfly-container');
    const colors = ['#4E54C8', '#8F94FB', '#A770EF', '#CF8BF3', '#FDB99B'];
    
    for (let i = 0; i < 10; i++) {
        const butterfly = document.createElement('div');
        butterfly.classList.add('butterfly');
        
        // Random position, size, and color
        const size = Math.random() * 20 + 10;
        const color = colors[Math.floor(Math.random() * colors.length)];
        
        butterfly.style.width = `${size}px`;
        butterfly.style.height = `${size}px`;
        butterfly.style.background = color;
        butterfly.style.left = `${Math.random() * 100}%`;
        butterfly.style.animationDuration = `${Math.random() * 5 + 5}s`;
        butterfly.style.animationDelay = `${Math.random() * 5}s`;
        
        container.appendChild(butterfly);
    }
}

// Create match animation
function createMatchAnimation() {
    // Add animation classes to match results
    const matchResults = document.querySelectorAll('.match-result');
    matchResults.forEach((result, index) => {
        result.classList.add('fade-in');
        result.style.animationDelay = `${index * 0.2}s`;
    });
    
    // Add pulse animation to match scores
    const matchScores = document.querySelectorAll('.match-score');
    matchScores.forEach(score => {
        score.classList.add('pulse');
    });
}

// Initialize animations when page loads
document.addEventListener('DOMContentLoaded', function() {
    createButterflies();
    createColorfulButterflies();
    createMatchAnimation();
});

// Fade-in and slide-in on scroll
function revealOnScroll() {
  const fadeInUps = document.querySelectorAll('.fade-in-up');
  const slideInLefts = document.querySelectorAll('.slide-in-left');
  const appearElements = [...fadeInUps, ...slideInLefts];
  const windowHeight = window.innerHeight;
  appearElements.forEach(el => {
    const rect = el.getBoundingClientRect();
    if (rect.top < windowHeight - 60) {
      el.classList.add('visible');
    }
  });
}
window.addEventListener('scroll', revealOnScroll);
window.addEventListener('DOMContentLoaded', revealOnScroll);

// Apply fade-in-up and slide-in-left classes to cards and headers
function applyRevealClasses() {
  document.querySelectorAll('.card').forEach((el, i) => {
    el.classList.add(i % 2 === 0 ? 'fade-in-up' : 'slide-in-left');
    el.style.animationDelay = `${i * 0.2}s`;
  });
  document.querySelectorAll('header, footer').forEach((el, i) => {
    el.classList.add('fade-in-up');
    el.style.animationDelay = `${i * 0.2}s`;
  });
}
document.addEventListener('DOMContentLoaded', applyRevealClasses);