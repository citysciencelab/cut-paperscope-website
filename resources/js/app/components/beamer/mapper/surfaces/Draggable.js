class Draggable {

	constructor(pInst, x, y) {
        if (x === void 0) { x = 0; }
        if (y === void 0) { y = 0; }
        this.pInst = pInst;
        this.x = x;
        this.y = y;
        this.clickX = 0;
        this.clickY = 0;
        this.xStartDrag = this.x;
        this.yStartDrag = this.y;
    }

	startDrag() {
        this.xStartDrag = this.x;
        this.yStartDrag = this.y;
        this.clickX = this.pInst.mouseX;
        this.clickY = this.pInst.mouseY;
    }

    moveTo() {
        this.x = this.xStartDrag + this.pInst.mouseX - this.clickX;
        this.y = this.yStartDrag + this.pInst.mouseY - this.clickY;
    }
}

export default Draggable;
