class ImageZoom {
    constructor(imageId) {
        this.image = document.getElementById(imageId);
        if (!this.image) return;

        this.init();
    }

    init() {
        // Create container for main image
        this.container = document.createElement('div');
        this.container.className = 'main-image-container';
        this.image.parentElement.insertBefore(this.container, this.image);
        this.container.appendChild(this.image);

        // Create zoom lens
        this.lens = document.createElement('div');
        this.lens.className = 'zoom-lens';
        this.container.appendChild(this.lens);

        // Create zoom result
        this.zoomResult = document.createElement('div');
        this.zoomResult.className = 'zoom-result';
        document.body.appendChild(this.zoomResult);

        // Calculate ratios
        this.cx = this.zoomResult.offsetWidth / this.lens.offsetWidth;
        this.cy = this.zoomResult.offsetHeight / this.lens.offsetHeight;

        // Set background
        this.zoomResult.style.backgroundImage = `url('${this.image.src}')`;
        this.zoomResult.style.backgroundSize = `${this.image.width * this.cx}px ${this.image.height * this.cy}px`;

        // Bind event listeners
        this.container.addEventListener('mouseenter', () => this.showZoom());
        this.container.addEventListener('mouseleave', () => this.hideZoom());
        this.container.addEventListener('mousemove', (e) => this.moveLens(e));
    }

    cleanup() {
        // Remove zoom elements
        if (this.lens) this.lens.remove();
        if (this.zoomResult) this.zoomResult.remove();
        
        // Remove the container but keep the image
        if (this.container && this.image) {
            this.container.parentElement.appendChild(this.image);
            this.container.remove();
        }
    }

    moveLens(e) {
        e.preventDefault();
        const pos = this.getCursorPos(e);
        let x = pos.x - (this.lens.offsetWidth / 2);
        let y = pos.y - (this.lens.offsetHeight / 2);

        // Prevent lens from going outside image
        if (x > this.image.width - this.lens.offsetWidth) x = this.image.width - this.lens.offsetWidth;
        if (x < 0) x = 0;
        if (y > this.image.height - this.lens.offsetHeight) y = this.image.height - this.lens.offsetHeight;
        if (y < 0) y = 0;

        // Set lens position
        this.lens.style.left = x + "px";
        this.lens.style.top = y + "px";

        // Move zoomed image
        this.zoomResult.style.backgroundPosition = `-${x * this.cx}px -${y * this.cy}px`;
    }

    getCursorPos(e) {
        const rect = this.image.getBoundingClientRect();
        return {
            x: e.clientX - rect.left,
            y: e.clientY - rect.top
        };
    }

    showZoom() {
        this.lens.style.display = 'block';
        this.zoomResult.style.display = 'block';
    }

    hideZoom() {
        this.lens.style.display = 'none';
        this.zoomResult.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', function() {
    // Keep track of current zoom instance
    let currentZoom = new ImageZoom('mainImage');

    // Update thumbnail click handler
    const thumbnails = document.querySelectorAll('.thumbnail');
    thumbnails.forEach(thumb => {
        thumb.addEventListener('click', function() {
            const mainImage = document.getElementById('mainImage');
            
            // Cleanup existing zoom
            if (currentZoom) {
                currentZoom.cleanup();
            }

            // Update main image
            mainImage.src = this.src;
            
            // Wait for image to load before initializing new zoom
            mainImage.onload = function() {
                currentZoom = new ImageZoom('mainImage');
            };
            
            // Update active thumbnail
            thumbnails.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
        });
    });

    // Function to change image (update this if you're using it elsewhere)
    function changeImage(src) {
        const mainImage = document.getElementById('mainImage');
        
        // Cleanup existing zoom
        if (currentZoom) {
            currentZoom.cleanup();
        }

        // Update main image
        mainImage.src = src;
        
        // Wait for image to load before initializing new zoom
        mainImage.onload = function() {
            currentZoom = new ImageZoom('mainImage');
        };
        
        // Update active thumbnail
        thumbnails.forEach(thumb => {
            thumb.classList.remove('active');
            if (thumb.src === src) {
                thumb.classList.add('active');
            }
        });
    }

    // Make changeImage function available globally if needed
    window.changeImage = changeImage;
}); 