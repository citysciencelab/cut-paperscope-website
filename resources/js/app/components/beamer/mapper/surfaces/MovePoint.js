/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class MovePoint {


	constructor(parent, x, y, pInst) {

		this.pInst = pInst;
		this.x = x;
		this.y = y;
		this.type = "CPOINT";
		this.r = 8;
		this.parent = parent;
		this.xStartDrag = this.x;
		this.yStartDrag = this.y;
		this.clickX = 0;
		this.clickY = 0;
		this.col = this.pInst.color(0, 255, 255);

		this.isControlPoint = false;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	EVENTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	isMouseOver() {

		var mx = this.pInst.mouseX;
		var my = this.pInst.mouseY;

		// updated for p5.js 1.9
		mx -= this.pInst.width / 2;
		my -= this.pInst.height / 2;

		var d = this.pInst.dist(mx, my, this.x + this.parent.x, this.y + this.parent.y);
		return d < this.r;
	}


	set(point) {

		this.x = point.x;
		this.y = point.y;
	}


	startDrag() {

		window.activeControlPoint = this;

		this.xStartDrag = this.x;
		this.yStartDrag = this.y;
		this.clickX = this.pInst.mouseX;
		this.clickY = this.pInst.mouseY;
	}


	moveToMouse() {

		this.x = this.pInst.mouseX - this.pInst.width / 2;
		this.y = this.pInst.mouseY - this.pInst.height / 2;
	}


	moveTo() {

		this.x = this.xStartDrag + this.pInst.mouseX - this.clickX;
		this.y = this.yStartDrag + this.pInst.mouseY - this.clickY;
	}


	setControlPoint(cp) {

		this.isControlPoint = cp;
	}


	interpolateBetween(start, end, f) {

		this.x = start.x + (end.x - start.x) * f;
		this.y = start.y + (end.y - start.y) * f;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RENDER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	display(col) {

		if (col === void 0) { col = this.col; }
		if(!this.pInst.isMovingPoints()) { return; }

		var c = col;
		if(this.isMouseOver() || window.activeControlPoint === this) {
			c = this.pInst.color(255);
		}

		this.pInst.push();
		this.pInst.translate(0, 0, 5);
		this.pInst.stroke(c);
		this.pInst.strokeWeight(2);
		this.pInst.noFill();
		this.pInst.ellipse(this.x, this.y, this.r * 2);
		this.pInst.fill(c);
		this.pInst.ellipse(this.x, this.y, this.r);
		this.pInst.pop();
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/

}

export default MovePoint;
