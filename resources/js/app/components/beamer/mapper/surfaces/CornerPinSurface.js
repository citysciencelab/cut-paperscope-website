/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import MeshPoint from './MeshPoint';
	import Surface from './Surface';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class CornerPinSurface extends Surface {


	constructor(id, w, h, res, type, buffer, pInst) {

		super(id, w, h, res, type, buffer, pInst);

		this.perspT = null;
		this.initMesh();
		this.calculateMesh();
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MESH
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	initMesh() {

		this.mesh = [];

		for (let y = 0; y < this.res; y++) {
			for (let x = 0; x < this.res; x++) {
				let mx = Math.floor(this.pInst.map(x, 0, this.res, 0, this.width));
				let my = Math.floor(this.pInst.map(y, 0, this.res, 0, this.height));
				let u = this.pInst.map(x, 0, this.res, 0, 1);
				let v = this.pInst.map(y, 0, this.res, 0, 1);
				this.mesh[y * this.res + x] = new MeshPoint(this, mx, my, u, v, this.pInst);
			}
		}

		this.TL = 0 + 0; // x + y
		this.TR = this.res - 1 + 0;
		this.BL = 0 + (this.res - 1) * (this.res);
		this.BR = this.res - 1 + (this.res - 1) * (this.res);

		// make the corners control points
		this.mesh[this.TL].setControlPoint(true);
		this.mesh[this.TR].setControlPoint(true);
		this.mesh[this.BR].setControlPoint(true);
		this.mesh[this.BL].setControlPoint(true);

		this.controlPoints = [];
		this.controlPoints.push(this.mesh[this.TL]);
		this.controlPoints.push(this.mesh[this.TR]);
		this.controlPoints.push(this.mesh[this.BR]);
		this.controlPoints.push(this.mesh[this.BL]);
	}


	getControlPoints() { return this.controlPoints; }
	calculateMesh() {} // abstract



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	LOAD / SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	load(json) {

		const { x, y, points } = json;

		this.x = x;
		this.y = y;

		for (const point of points) {
			let mp = this.mesh[point.i];
			mp.x = point.x;
			mp.y = point.y;
			mp.u = point.u;
			mp.v = point.v;
		}

		this.calculateMesh();
	}


	getJson() {

		let sJson = {};
		sJson.id = this.id;
		sJson.res = this.res;
		sJson.x = this.x;
		sJson.y = this.y;
		sJson.w = this.w;
		sJson.h = this.h;
		sJson.type = this.type;
		sJson.points = [];

		for (let i = 0; i < this.mesh.length; i++) {
			if (this.mesh[i].isControlPoint) {
				let point = {};
				point.i = i;
				point.x = this.mesh[i].x;
				point.y = this.mesh[i].y;
				point.u = this.mesh[i].u;
				point.v = this.mesh[i].v;
				sJson.points.push(point);
			}
		}

		return sJson;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	EVENTS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	selectSurface() {

		// if the surface itself is selected
		if (this.isMouseOver()) {
			this.startDrag();
			return this;
		}
		return null;
	}


	selectPoints() {

		// check if control points are selected
		let cp = this.isMouseOverControlPoints();
		if (cp) {
			cp.startDrag();
			return cp;
		}
	}


	isMouseOverControlPoints() {

		for (const cp of this.controlPoints) {
			if (cp.isMouseOver()) {
				return cp;
			}
		}
		return false;
	}


	isPointInTriangle(x, y, a, b, c) {

		let v0 = this.pInst.createVector(c.x - a.x, c.y - a.y);
		let v1 = this.pInst.createVector(b.x - a.x, b.y - a.y);
		let v2 = this.pInst.createVector(x - a.x, y - a.y);

		let dot00 = v0.dot(v0);
		let dot01 = v1.dot(v0);
		let dot02 = v2.dot(v0);
		let dot11 = v1.dot(v1);
		let dot12 = v2.dot(v1);

		// Compute barycentric coordinates
		let invDenom = 1 / (dot00 * dot11 - dot01 * dot01);
		let u = (dot11 * dot02 - dot01 * dot12) * invDenom;
		let v = (dot00 * dot12 - dot01 * dot02) * invDenom;

		// Check if point is in triangle
		return (u > 0) && (v > 0) && (u + v < 1);
	}


	displayControlPoints() {

		this.pInst.push();
		this.pInst.translate(this.x, this.y);
		for (const p of this.controlPoints)
			p.display(this.controlPointColor);
		this.pInst.pop();
	}


	/**
	* This function will give you the position of the mouse in the surface's
	* coordinate system.
	*/

	getTransformedCursor(cx, cy) {

		let point = this.perspT(cx - this.x, cy - this.y);
		return this.pInst.createVector(point[0], point[1]);
	}


	getTransformedMouse() {

		return getTransformedCursor(this.pInst.mouseX, this.pInst.mouseY);
	}

	// 2d cross product
	cross2(x0, y0, x1, y1) {

		return x0 * y1 - y0 * x1;
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/

}

export default CornerPinSurface;
