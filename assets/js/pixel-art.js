class PixelArtStudio {
    undoStack = [];
    redoStack = [];
    constructor() {
        this.canvas = document.getElementById("pixelCanvas");
        this.ctx = this.canvas.getContext("2d");
        this.grid = document.getElementById("canvasGrid");

        this.gridSize = 40;
        this.pixelSize = 16;
        this.currentTool = "brush";
        this.primaryColor = "#000000";
        this.isDrawing = false;
        this.zoom = 1;

        this.pixels = {};

        this.init();
        document.getElementById("undoBtn").addEventListener("click", (e) => {
            e.preventDefault();
            this.undo();
        });
        document.getElementById("redoBtn").addEventListener("click", (e) => {
            e.preventDefault();
            this.redo();
        });
    }

    init() {
        this.setupCanvas();
        this.setupEventListeners();
        this.updateGrid();
        this.clearCanvas();
    }

    setupCanvas() {
        this.canvas.width = this.gridSize * this.pixelSize;
        this.canvas.height = this.gridSize * this.pixelSize;
        this.ctx.imageSmoothingEnabled = false;
    }

    setupEventListeners() {
        this.canvas.addEventListener("mousedown", (e) => this.startDrawing(e));
        this.canvas.addEventListener("mousemove", (e) => this.draw(e));
        this.canvas.addEventListener("mouseup", () => this.stopDrawing());
        this.canvas.addEventListener("mouseleave", () => this.stopDrawing());
        this.canvas.addEventListener("contextmenu", (e) => e.preventDefault());

        document.querySelectorAll(".tool-btn").forEach((btn) => {
            if (btn.dataset.tool) {
                btn.addEventListener("click", (e) => {
                    const activeBtn =
                        document.querySelector(".tool-btn.active");
                    if (activeBtn) activeBtn.classList.remove("active");
                    btn.classList.add("active");
                    this.currentTool = btn.dataset.tool;
                });
            }
        });

        document.querySelectorAll(".color-item").forEach((item) => {
            item.addEventListener("click", (e) => {
                this.primaryColor = item.dataset.color;
                document.getElementById("primaryColor").style.backgroundColor =
                    this.primaryColor;
                this.switchToBrush();
            });
        });

        document
            .getElementById("primaryColor")
            .addEventListener("click", () => {
                document.getElementById("colorPicker").value =
                    this.primaryColor;
                document.getElementById("colorPicker").click();
            });

        document
            .getElementById("colorPicker")
            .addEventListener("change", (e) => {
                this.primaryColor = e.target.value;
                document.getElementById("primaryColor").style.backgroundColor =
                    this.primaryColor;
                this.switchToBrush();
            });

        document
            .getElementById("canvasSize")
            .addEventListener("change", (e) => {
                this.gridSize = parseInt(e.target.value);
                this.setupCanvas();
                this.updateGrid();
                this.clearCanvas();
            });

        document.getElementById("clearCanvas").addEventListener("click", () => {
            if (confirm("Kanvası temizlemek istediğinizden emin misiniz?")) {
                this.clearCanvas();
            }
        });

        document
            .getElementById("downloadCanvas")
            .addEventListener("click", () => this.downloadCanvas());
        document
            .getElementById("saveCanvas")
            .addEventListener("click", () => this.saveCanvas());

        document
            .getElementById("zoomIn")
            .addEventListener("click", () => this.changeZoom(0.1));
        document
            .getElementById("zoomOut")
            .addEventListener("click", () => this.changeZoom(-0.1));
    }

    getCanvasPosition(e) {
        const rect = this.canvas.getBoundingClientRect();
        const x = Math.floor(
            (e.clientX - rect.left) / (this.pixelSize * this.zoom)
        );
        const y = Math.floor(
            (e.clientY - rect.top) / (this.pixelSize * this.zoom)
        );
        return { x, y };
    }

    switchToBrush() {
        if (this.currentTool !== "brush") {
            document
                .querySelector(".tool-btn.active")
                .classList.remove("active");
            document
                .querySelector('[data-tool="brush"]')
                .classList.add("active");
            this.currentTool = "brush";
        }
    }

    startDrawing(e) {
        this.isDrawing = true;
        this.hasDrawn = false;
        this.pushUndo();
        this.draw(e);
    }

    stopDrawing() {
        this.isDrawing = false;
    }

    draw(e) {
        if (!this.isDrawing && this.currentTool !== "picker") return;

        const pos = this.getCanvasPosition(e);
        const color = this.primaryColor;
        let changed = false;

        switch (this.currentTool) {
            case "brush":
                changed = this.setPixel(pos.x, pos.y, color);
                break;
            case "eraser":
                changed = this.erasePixel(pos.x, pos.y);
                break;
            case "bucket":
                if (this.isDrawing) {
                    this.floodFill(pos.x, pos.y, color);
                    changed = true;
                }
                break;
            case "picker":
                if (this.isDrawing) {
                    const pickedColor = this.getPixel(pos.x, pos.y);
                    if (pickedColor) {
                        this.primaryColor = pickedColor;
                        document.getElementById(
                            "primaryColor"
                        ).style.backgroundColor = pickedColor;
                        document.getElementById("colorPicker").value =
                            pickedColor;
                        this.switchToBrush();
                    }
                }
                break;
        }
        if (changed) this.hasDrawn = true;
    }

    setPixel(x, y, color) {
        if (x < 0 || x >= this.gridSize || y < 0 || y >= this.gridSize)
            return false;
        const key = `${x},${y}`;
        if (this.pixels[key] === color) return false;
        this.pixels[key] = color;
        this.ctx.fillStyle = color;
        this.ctx.fillRect(
            x * this.pixelSize,
            y * this.pixelSize,
            this.pixelSize,
            this.pixelSize
        );
        return true;
    }

    erasePixel(x, y) {
        if (x < 0 || x >= this.gridSize || y < 0 || y >= this.gridSize)
            return false;
        const key = `${x},${y}`;
        if (!(key in this.pixels)) return false;
        delete this.pixels[key];
        this.ctx.clearRect(
            x * this.pixelSize,
            y * this.pixelSize,
            this.pixelSize,
            this.pixelSize
        );
        return true;
    }
    pushUndo() {
        this.undoStack.push({
            pixels: JSON.stringify(this.pixels),
            gridSize: this.gridSize,
        });
        if (this.undoStack.length > 100) this.undoStack.shift();
        this.redoStack = [];
    }

    undo() {
        if (this.undoStack.length === 0) return;
        this.redoStack.push({
            pixels: JSON.stringify(this.pixels),
            gridSize: this.gridSize,
        });
        const prev = this.undoStack.pop();
        this.gridSize = prev.gridSize;
        document.getElementById("canvasSize").value = String(prev.gridSize);
        this.setupCanvas();
        this.updateGrid();
        this.clearCanvas();
        this.pixels = JSON.parse(prev.pixels);
        Object.entries(this.pixels).forEach(([key, color]) => {
            const [x, y] = key.split(",").map(Number);
            this.ctx.fillStyle = color;
            this.ctx.fillRect(
                x * this.pixelSize,
                y * this.pixelSize,
                this.pixelSize,
                this.pixelSize
            );
        });
    }

    redo() {
        if (this.redoStack.length === 0) return;
        this.undoStack.push({
            pixels: JSON.stringify(this.pixels),
            gridSize: this.gridSize,
        });
        const next = this.redoStack.pop();
        this.gridSize = next.gridSize;
        document.getElementById("canvasSize").value = String(next.gridSize);
        this.setupCanvas();
        this.updateGrid();
        this.clearCanvas();
        this.pixels = JSON.parse(next.pixels);
        Object.entries(this.pixels).forEach(([key, color]) => {
            const [x, y] = key.split(",").map(Number);
            this.ctx.fillStyle = color;
            this.ctx.fillRect(
                x * this.pixelSize,
                y * this.pixelSize,
                this.pixelSize,
                this.pixelSize
            );
        });
    }

    getPixel(x, y) {
        return this.pixels[`${x},${y}`] || null;
    }

    floodFill(startX, startY, newColor) {
        const targetColor = this.getPixel(startX, startY);
        if (targetColor === newColor) return;

        const stack = [[startX, startY]];
        const visited = new Set();

        while (stack.length > 0) {
            const [x, y] = stack.pop();
            const key = `${x},${y}`;

            if (visited.has(key)) continue;
            if (x < 0 || x >= this.gridSize || y < 0 || y >= this.gridSize)
                continue;
            if (this.getPixel(x, y) !== targetColor) continue;

            visited.add(key);
            this.setPixel(x, y, newColor);

            stack.push([x + 1, y], [x - 1, y], [x, y + 1], [x, y - 1]);
        }
    }

    clearCanvas() {
        this.pixels = {};
        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);
    }

    updateGrid() {
        this.grid.style.backgroundSize = `${this.pixelSize * this.zoom}px ${
            this.pixelSize * this.zoom
        }px`;
    }

    changeZoom(delta) {
        this.zoom = Math.max(0.5, Math.min(2, this.zoom + delta));
        const newSize = this.gridSize * this.pixelSize * this.zoom;

        this.canvas.style.width = `${newSize}px`;
        this.canvas.style.height = `${newSize}px`;

        this.updateGrid();
        document.getElementById("zoomLevel").textContent = `${Math.round(
            this.zoom * 100
        )}%`;
    }

    downloadCanvas() {
        const tempCanvas = document.createElement("canvas");
        tempCanvas.width = this.gridSize;
        tempCanvas.height = this.gridSize;
        const tempCtx = tempCanvas.getContext("2d");
        tempCtx.imageSmoothingEnabled = false;

        Object.entries(this.pixels).forEach(([key, color]) => {
            const [x, y] = key.split(",").map(Number);
            tempCtx.fillStyle = color;
            tempCtx.fillRect(x, y, 1, 1);
        });

        const link = document.createElement("a");
        link.download = `pixel-art-${Date.now()}.png`;
        link.href = tempCanvas.toDataURL();
        link.click();
    }

    saveCanvas() {
        const imageData = JSON.stringify({
            gridSize: this.gridSize,
            pixels: this.pixels,
            timestamp: Date.now(),
        });

        const savedArtworks = JSON.parse(
            localStorage.getItem("pixelArtworks") || "[]"
        );
        savedArtworks.push(imageData);
        localStorage.setItem("pixelArtworks", JSON.stringify(savedArtworks));

        alert("Eseriniz başarıyla kaydedildi!");
    }
}

document.addEventListener("keydown", (e) => {
    if (e.ctrlKey || e.metaKey) {
        switch (e.key.toLowerCase()) {
            case "s":
                e.preventDefault();
                document.getElementById("saveCanvas").click();
                break;
            case "z":
                e.preventDefault();
                if (window.pixelArtStudioInstance)
                    window.pixelArtStudioInstance.undo();
                break;
            case "y":
                e.preventDefault();
                if (window.pixelArtStudioInstance)
                    window.pixelArtStudioInstance.redo();
                break;
        }
    }

    switch (e.key.toLowerCase()) {
        case "b":
            document.querySelector('[data-tool="brush"]').click();
            break;
        case "e":
            document.querySelector('[data-tool="eraser"]').click();
            break;
        case "g":
            document.querySelector('[data-tool="bucket"]').click();
            break;
        case "i":
            document.querySelector('[data-tool="picker"]').click();
            break;
    }
});

function initializePixelArt() {
    // Eğer zaten bir instance varsa, temizle
    if (window.pixelArtStudioInstance) {
        // Mevcut event listener'ları temizle
        const canvas = document.getElementById("pixelCanvas");
        if (canvas) {
            canvas.replaceWith(canvas.cloneNode(true));
        }
    }
    
    window.pixelArtStudioInstance = new PixelArtStudio();
    
    function renderSavedGallery() {
        const gallery = document.getElementById("savedGallery");
        if (!gallery) return;
        gallery.innerHTML = "";
        const savedArtworks = JSON.parse(
            localStorage.getItem("pixelArtworks") || "[]"
        );
        if (savedArtworks.length === 0) {
            gallery.innerHTML =
                '<div style="color:#fff;opacity:.7;text-align:center;width:100%">Henüz kaydedilmiş tasarım yok.</div>';
            return;
        }
        savedArtworks.forEach((data, idx) => {
            let parsed;
            try {
                parsed = typeof data === "string" ? JSON.parse(data) : data;
            } catch {
                return;
            }
            const { gridSize, pixels } = parsed;
            const canvas = document.createElement("canvas");
            canvas.width = gridSize;
            canvas.height = gridSize;
            canvas.style.width = "80px";
            canvas.style.height = "80px";
            canvas.style.background = "#fff";
            canvas.style.borderRadius = "8px";
            canvas.style.margin = "auto";
            const ctx = canvas.getContext("2d");
            ctx.imageSmoothingEnabled = false;
            Object.entries(pixels).forEach(([key, color]) => {
                const [x, y] = key.split(",").map(Number);
                ctx.fillStyle = color;
                ctx.fillRect(x, y, 1, 1);
            });
            const item = document.createElement("div");
            item.className = "gallery-item";
            item.style.position = "relative";
            item.appendChild(canvas);
            const delBtn = document.createElement("button");
            delBtn.innerHTML =
                '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>';
            delBtn.title = "Sil";
            delBtn.style.position = "absolute";
            delBtn.style.top = "6px";
            delBtn.style.right = "6px";
            delBtn.style.background = "rgba(0,0,0,0.7)";
            delBtn.style.border = "none";
            delBtn.style.borderRadius = "50%";
            delBtn.style.color = "#fff";
            delBtn.style.width = "28px";
            delBtn.style.height = "28px";
            delBtn.style.display = "flex";
            delBtn.style.alignItems = "center";
            delBtn.style.justifyContent = "center";
            delBtn.style.cursor = "pointer";
            delBtn.style.zIndex = "2";
            delBtn.addEventListener("click", function (e) {
                e.stopPropagation();
                if (confirm("Bu tasarımı silmek istediğinize emin misiniz?")) {
                    const arr = JSON.parse(
                        localStorage.getItem("pixelArtworks") || "[]"
                    );
                    arr.splice(idx, 1);
                    localStorage.setItem("pixelArtworks", JSON.stringify(arr));
                    renderSavedGallery();
                }
            });
            item.appendChild(delBtn);
            item.title = "Düzenlemek için tıkla";
            item.style.cursor = "pointer";
            item.addEventListener("click", function () {
                if (window.pixelArtStudioInstance) {
                    window.pixelArtStudioInstance.loadFromData(parsed);
                }
            });
            gallery.appendChild(item);
        });
    }
    renderSavedGallery();
    window.addEventListener("storage", renderSavedGallery);
    const origSave = PixelArtStudio.prototype.saveCanvas;
    PixelArtStudio.prototype.saveCanvas = function () {
        origSave.call(this);
        renderSavedGallery();
    };
    PixelArtStudio.prototype.loadFromData = function (data) {
        this.gridSize = data.gridSize;
        document.getElementById("canvasSize").value = String(data.gridSize);
        this.setupCanvas();
        this.updateGrid();
        this.clearCanvas();
        this.pixels = Object.assign({}, data.pixels);
        Object.entries(this.pixels).forEach(([key, color]) => {
            const [x, y] = key.split(",").map(Number);
            this.ctx.fillStyle = color;
            this.ctx.fillRect(
                x * this.pixelSize,
                y * this.pixelSize,
                this.pixelSize,
                this.pixelSize
            );
        });
    };
}

// Hem normal sayfa yüklemesi hem de Turbo sayfa geçişleri için event listener'lar
document.addEventListener("DOMContentLoaded", initializePixelArt);
document.addEventListener("turbo:load", initializePixelArt);
document.addEventListener("turbo:render", initializePixelArt);
