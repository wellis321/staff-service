/**
 * Signature Pad Component
 * Allows users to draw or upload their signature
 */

class SignaturePad {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) {
            throw new Error(`Container with id "${containerId}" not found`);
        }
        
        this.options = {
            width: options.width || 600,
            height: options.height || 200,
            backgroundColor: options.backgroundColor || '#ffffff',
            penColor: options.penColor || '#000000',
            ...options
        };
        
        this.canvas = null;
        this.ctx = null;
        this.isDrawing = false;
        this.currentSignature = null;
        this.init();
    }
    
    init() {
        // Create canvas
        this.canvas = document.createElement('canvas');
        this.canvas.width = this.options.width;
        this.canvas.height = this.options.height;
        this.canvas.style.border = '2px solid #e5e7eb';
        this.canvas.style.cursor = 'crosshair';
        this.canvas.style.backgroundColor = this.options.backgroundColor;
        
        this.ctx = this.canvas.getContext('2d');
        this.ctx.strokeStyle = this.options.penColor;
        this.ctx.lineWidth = 2;
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
        
        // Add event listeners
        this.canvas.addEventListener('mousedown', (e) => this.startDrawing(e));
        this.canvas.addEventListener('mousemove', (e) => this.draw(e));
        this.canvas.addEventListener('mouseup', () => this.stopDrawing());
        this.canvas.addEventListener('mouseout', () => this.stopDrawing());
        
        // Touch events for mobile
        this.canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousedown', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            this.canvas.dispatchEvent(mouseEvent);
        });
        
        this.canvas.addEventListener('touchmove', (e) => {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent('mousemove', {
                clientX: touch.clientX,
                clientY: touch.clientY
            });
            this.canvas.dispatchEvent(mouseEvent);
        });
        
        this.canvas.addEventListener('touchend', (e) => {
            e.preventDefault();
            const mouseEvent = new MouseEvent('mouseup', {});
            this.canvas.dispatchEvent(mouseEvent);
        });
        
        // Append canvas to container
        this.container.appendChild(this.canvas);
    }
    
    startDrawing(e) {
        this.isDrawing = true;
        const rect = this.canvas.getBoundingClientRect();
        this.ctx.beginPath();
        this.ctx.moveTo(e.clientX - rect.left, e.clientY - rect.top);
    }
    
    draw(e) {
        if (!this.isDrawing) return;
        
        const rect = this.canvas.getBoundingClientRect();
        this.ctx.lineTo(e.clientX - rect.left, e.clientY - rect.top);
        this.ctx.stroke();
        // Update signature data as user draws
        this.updateSignature();
    }
    
    stopDrawing() {
        if (this.isDrawing) {
            this.isDrawing = false;
            this.updateSignature();
        }
    }
    
    clear() {
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
        this.currentSignature = null;
    }
    
    updateSignature() {
        this.currentSignature = this.canvas.toDataURL('image/png');
    }
    
    getSignature() {
        if (!this.currentSignature) {
            this.updateSignature();
        }
        return this.currentSignature;
    }
    
    setSignature(dataUrl) {
        const img = new Image();
        img.onload = () => {
            this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
            this.ctx.drawImage(img, 0, 0, this.canvas.width, this.canvas.height);
            this.currentSignature = dataUrl;
        };
        img.src = dataUrl;
    }
    
    isEmpty() {
        const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
        const data = imageData.data;
        
        // Check if canvas is empty (all pixels are background color - white)
        // We check if there are any non-white pixels (RGB not all 255 or alpha not 255)
        for (let i = 0; i < data.length; i += 4) {
            const r = data[i];
            const g = data[i + 1];
            const b = data[i + 2];
            const a = data[i + 3];
            
            // If pixel is not white (or not fully transparent), canvas has content
            if (a > 0 && (r !== 255 || g !== 255 || b !== 255)) {
                return false;
            }
        }
        return true;
    }
    
    hasContent() {
        return !this.isEmpty();
    }
}

