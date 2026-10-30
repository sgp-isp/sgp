/**
 * SgpPatternLock — Componente de Patrón de Desbloqueo Gestual de 9 Puntos (3x3)
 * Sistema de Gestión de Pasantes (SGP) - ISPEB
 *
 * Soporte completo para Mouse y Pantallas Táctiles (Smartphones y Tablets).
 * Microanimaciones, feedback háptico y estados visuales (Normal, Éxito, Error).
 */

class SgpPatternLock {
    constructor(canvasId, options = {}) {
        this.canvas = typeof canvasId === 'string' ? document.getElementById(canvasId) : canvasId;
        if (!this.canvas) {
            console.error('[SgpPatternLock] Canvas no encontrado:', canvasId);
            return;
        }

        this.ctx = this.canvas.getContext('2d');
        this.size = options.size || 280;
        this.canvas.width = this.size;
        this.canvas.height = this.size;

        this.onComplete = options.onComplete || function () {};
        this.onChange   = options.onChange || function () {};

        this.gridSize = 3;
        this.points = [];
        this.selectedIndices = [];
        this.isDrawing = false;
        this.currentPos = { x: 0, y: 0 };
        this.state = 'normal'; // 'normal', 'success', 'error'

        this.colors = {
            normal: {
                dotOuter: '#cbd5e1',
                dotInner: '#64748b',
                dotActive: '#2563eb',
                halo: 'rgba(37, 99, 235, 0.25)',
                line: '#2563eb'
            },
            success: {
                dotOuter: '#86efac',
                dotInner: '#16a34a',
                dotActive: '#16a34a',
                halo: 'rgba(22, 163, 74, 0.25)',
                line: '#16a34a'
            },
            error: {
                dotOuter: '#fca5a5',
                dotInner: '#dc2626',
                dotActive: '#dc2626',
                halo: 'rgba(220, 38, 38, 0.25)',
                line: '#dc2626'
            }
        };

        this.initPoints();
        this.bindEvents();
        this.draw();
    }

    initPoints() {
        this.points = [];
        const padding = this.size * 0.16;
        const spacing = (this.size - padding * 2) / (this.gridSize - 1);
        let index = 1;

        for (let r = 0; r < this.gridSize; r++) {
            for (let c = 0; c < this.gridSize; c++) {
                this.points.push({
                    index: index++,
                    x: padding + c * spacing,
                    y: padding + r * spacing,
                    radius: this.size * 0.05
                });
            }
        }
    }

    bindEvents() {
        // Eventos Mouse
        this.canvas.addEventListener('mousedown', (e) => this.onStart(e));
        window.addEventListener('mousemove', (e) => this.onMove(e));
        window.addEventListener('mouseup', (e) => this.onEnd(e));

        // Eventos Touch (Móviles / Tablets)
        this.canvas.addEventListener('touchstart', (e) => {
            e.preventDefault();
            this.onStart(e.touches[0]);
        }, { passive: false });

        window.addEventListener('touchmove', (e) => {
            if (!this.isDrawing) return;
            e.preventDefault();
            this.onMove(e.touches[0]);
        }, { passive: false });

        window.addEventListener('touchend', (e) => {
            if (!this.isDrawing) return;
            this.onEnd(e);
        });
    }

    getCanvasPos(e) {
        const rect = this.canvas.getBoundingClientRect();
        const clientX = e.clientX !== undefined ? e.clientX : (e.touches && e.touches[0] ? e.touches[0].clientX : 0);
        const clientY = e.clientY !== undefined ? e.clientY : (e.touches && e.touches[0] ? e.touches[0].clientY : 0);

        return {
            x: clientX - rect.left,
            y: clientY - rect.top
        };
    }

    onStart(e) {
        const pos = this.getCanvasPos(e);
        if (pos.x < 0 || pos.x > this.size || pos.y < 0 || pos.y > this.size) return;

        this.state = 'normal';
        this.selectedIndices = [];
        this.isDrawing = true;
        this.currentPos = pos;

        this.checkPointCollision(pos);
        this.draw();
    }

    onMove(e) {
        if (!this.isDrawing) return;
        const pos = this.getCanvasPos(e);
        this.currentPos = pos;
        this.checkPointCollision(pos);
        this.draw();
    }

    onEnd() {
        if (!this.isDrawing) return;
        this.isDrawing = false;
        this.draw();

        if (this.selectedIndices.length > 0) {
            const pattern = this.getPattern();
            if (this.onComplete) {
                this.onComplete(pattern, this);
            }
        }
    }

    checkPointCollision(pos) {
        const hitRadius = this.size * 0.12; // Radio de detección ampliado para comodidad al tacto

        for (const pt of this.points) {
            if (this.selectedIndices.includes(pt.index)) continue;

            const dist = Math.hypot(pt.x - pos.x, pt.y - pos.y);
            if (dist <= hitRadius) {
                this.selectedIndices.push(pt.index);
                this.vibrate(20);
                if (this.onChange) {
                    this.onChange(this.getPattern());
                }
                break;
            }
        }
    }

    vibrate(ms) {
        try {
            if (navigator && navigator.vibrate) {
                navigator.vibrate(ms);
            }
        } catch (_) {}
    }

    draw() {
        this.ctx.clearRect(0, 0, this.size, this.size);
        const pal = this.colors[this.state] || this.colors.normal;

        // 1. Dibujar líneas conectoras entre puntos seleccionados
        if (this.selectedIndices.length > 0) {
            this.ctx.beginPath();
            const firstPt = this.points.find(p => p.index === this.selectedIndices[0]);
            this.ctx.moveTo(firstPt.x, firstPt.y);

            for (let i = 1; i < this.selectedIndices.length; i++) {
                const pt = this.points.find(p => p.index === this.selectedIndices[i]);
                this.ctx.lineTo(pt.x, pt.y);
            }

            // Si está dibujando activamente, extender hacia el dedo/cursor actual
            if (this.isDrawing && this.currentPos) {
                this.ctx.lineTo(this.currentPos.x, this.currentPos.y);
            }

            this.ctx.strokeStyle = pal.line;
            this.ctx.lineWidth = Math.max(5, this.size * 0.022);
            this.ctx.lineCap = 'round';
            this.ctx.lineJoin = 'round';
            this.ctx.stroke();
        }

        // 2. Dibujar los 9 puntos
        for (const pt of this.points) {
            const isSelected = this.selectedIndices.includes(pt.index);

            // Halo exterior en puntos seleccionados
            if (isSelected) {
                this.ctx.beginPath();
                this.ctx.arc(pt.x, pt.y, pt.radius * 2.2, 0, Math.PI * 2);
                this.ctx.fillStyle = pal.halo;
                this.ctx.fill();

                // Anillo intermedio
                this.ctx.beginPath();
                this.ctx.arc(pt.x, pt.y, pt.radius * 1.5, 0, Math.PI * 2);
                this.ctx.fillStyle = pal.dotActive;
                this.ctx.fill();

                // Centro blanco de contraste
                this.ctx.beginPath();
                this.ctx.arc(pt.x, pt.y, pt.radius * 0.65, 0, Math.PI * 2);
                this.ctx.fillStyle = '#ffffff';
                this.ctx.fill();
            } else {
                // Punto en reposo
                this.ctx.beginPath();
                this.ctx.arc(pt.x, pt.y, pt.radius * 0.8, 0, Math.PI * 2);
                this.ctx.fillStyle = pal.dotInner;
                this.ctx.fill();
            }
        }
    }

    getPattern() {
        return this.selectedIndices.join('-');
    }

    reset() {
        this.state = 'normal';
        this.selectedIndices = [];
        this.isDrawing = false;
        this.draw();
    }

    success() {
        this.state = 'success';
        this.vibrate([30, 40, 30]);
        this.draw();
    }

    error() {
        this.state = 'error';
        this.vibrate([70, 50, 70]);
        this.draw();
        setTimeout(() => {
            this.reset();
        }, 1200);
    }
}

// Hacer disponible globalmente
window.SgpPatternLock = SgpPatternLock;
