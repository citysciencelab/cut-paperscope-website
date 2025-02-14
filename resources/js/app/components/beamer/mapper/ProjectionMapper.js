/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import QuadMap from "./surfaces/QuadMap";
	import TriMap from "./surfaces/TriMap";
	import PolyMap from "./surfaces/PolyMap";
	import LineMap from "./lines/LineMap";



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class ProjectionMapper {

	constructor() {

		this.buffer;
		this.bufferWEBGL;

		this.surfaces = [];
		this.lines = [];

		this.dragged = null;
		this.selected = null;

		this.calibrate = false;
		this.pInst = null;
		this.pMousePressed = false;
		this.moveMode = "ALL";
		this.lastFrame = -1;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INIT
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	init(w, h) {

		this.pInst.isCalibratingMapper = () => this.calibrate;
		this.pInst.isMovingPoints = () => this.isMovingPoints();
		this.pInst.isDragging = (surface) => this.isDragging(surface);

		this.bufferWEBGL = this.pInst.createGraphics(w, h, this.pInst.WEBGL);
		this.buffer = this.pInst.createGraphics(w, h);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	SURFACES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	createQuadMap(w, h, res = 20) {

		const s = new QuadMap(this.surfaces.length, w, h, res, this.buffer, this.pInst);
		this.surfaces.push(s);

		return s;
	}


	createTriMap(w, h, res = 20) {

		const s = new TriMap(this.surfaces.length, w, h, res, this.buffer, this.pInst );
		this.surfaces.push(s);

		return s;
	}


	createLineMap(x0 = 0, y0 = 0, x1 = 0, y1 = 0) {

		if (x0 == 0 && y0 == 0 && x1 == 0 && y1 == 0) {
			x1 = 200;
			y0 = 30 * this.lines.length;
			y1 = 30 * this.lines.length;
		}

		const l = new LineMap(x0, y0, x1, y1, this.lines.length, this.pInst);
		this.lines.push(l);

		return l;
	}


	createPolyMap(numPoints = 3) {

		if (numPoints < 3) numPoints = 3;

		let s = new PolyMap(this.surfaces.length, numPoints, this.buffer, this.pInst);
		this.surfaces.push(s);

		return s;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INTERACTION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	onClick() {

		// only in calibration mode
		if(!this.calibrate) return;

		if(!this.checkPointsClick()) {
			this.checkSurfacesClick();
		}
	}

	// click on line or bezier control points
	checkPointsClick() {

		// Check Lines: navigate the list backwards, as to select
		for (let i = this.lines.length - 1; i >= 0; i--) {
			let s = this.lines[i];
			this.dragged = s.selectPoints();
			if (this.dragged != null) { return true; }
		}

		// TODO - check bez control points before anchors
		// check mapping surfaces
		for (let i = this.surfaces.length - 1; i >= 0; i--) {

			let s = this.surfaces[i];
			if(s.isHidden) { continue; }

			this.dragged = s.selectPoints();
			if (this.dragged != null) {
				this.selected = s;
				return true;
			}
		}

		this.selected = null;
		return false;
	}


	checkSurfacesClick() {

		// Check Lines: navigate the list backwards, as to select
		for (let i = this.lines.length - 1; i >= 0; i--) {
			let s = this.lines[i];
			this.dragged = s.selectSurface();
			if(this.dragged != null) {
				return true;
			}
		}

		// check mapping surfaces
		for(let i = this.surfaces.length - 1; i >= 0; i--) {

			let s = this.surfaces[i];
			if(s.isHidden) { continue; }
			this.dragged = s.selectSurface();
			if (this.dragged != null) {
				this.selected = s;
				return true;
			}
		}

		this.selected = null;
		return false;
	}


	isMovingPoints()	{ return this.moveMode == "ALL" || this.moveMode == "POINTS"; }
	onDrag()			{ this.dragged?.moveTo(); }
	onRelease()			{ this.dragged = null; }


	isDragging(surface) {

		if (this.dragged === null) return true;
		return this.dragged === surface;
	}


	updateEvents() {

		if (this.pInst.mouseIsPressed) {
			if (!this.pMousePressed) {
				this.onClick();
			}
			else {
				this.onDrag();
			}
		}
		else if(this.pMousePressed) {
			this.onRelease();
		}

		this.pMousePressed = this.pInst.mouseIsPressed;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	LOAD / SAVE
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	load(id) {

		let json = JSON.parse(localStorage.getItem("paperscope-calibration-" + id));
		if(!json) { return; }


		if (json.surfaces) this.loadSurfaces(json);
		if (json.lines) this.loadLines(json);
	}


	loadSurfaces(json) {

		let jSurfaces = json.surfaces;
		if (jSurfaces.length !== this.surfaces.length) {
			console.warn(`calibration  has ${jSurfaces.length} surface maps but there are ${this.surfaces.length} surface maps in memory` );
		}

		// TODO - don't remember what I was doing here...
		// in the future if we want to make sure only to load tris into tris, etc.
		const jTriSurfaces = jSurfaces.filter((surf) => surf.type === "TRI");
		const jQuadSurfaces = jSurfaces.filter((surf) => surf.type === "QUAD");
		const jPolySurfaces = jSurfaces.filter((surf) => surf.type === "POLY");

		const mapTris = this.surfaces.filter((surf) => surf.type === "TRI");
		const mapQuads = this.surfaces.filter((surf) => surf.type === "QUAD");
		const mapPolys = this.surfaces.filter((surf) => surf.type === "POLY");

		// loading tris
		let index = 0;
		while (index < jTriSurfaces.length && index < mapTris.length) {
			const s = mapTris[index];
			if (s.isEqual(mapTris[index])) s.load(jTriSurfaces[index]);
			else console.warn("mismatch between calibration surface types / ids");
			index++;
		}

		// loading quads
		index = 0;
		while (index < jQuadSurfaces.length && index < mapQuads.length) {
			const s = mapQuads[index];
			if (s.isEqual(mapQuads[index])) s.load(jQuadSurfaces[index]);
			else console.warn("mismatch between calibration surface types / ids");
			index++;
		}

		// loading poly
		index = 0;
		while (index < jPolySurfaces.length && index < mapPolys.length) {
			const s = mapPolys[index];

			if (s.isEqual(mapPolys[index])) {
				s.load(jPolySurfaces[index]);
			} else
				console.warn("mismatch between calibration poly surface types / ids");
			index++;
		}
	}


	loadLines(json) {

		let jLines = json.lines;
		if (jLines.length !== this.lines.length) {
			console.warn(`json calibration file has ${jLines.length} line maps but there are ${this.lines.length} line maps in memory`);
		}

		let index = 0;
		while (index < jLines.length && index < this.lines.length) {
			this.lines[index].load(jLines[index]);
			index++;
		}
	}


	save(id) {

		let json = { surfaces: [], lines: [] };
		for (const surface of this.surfaces) { json.surfaces.push(surface.getJson()); }
		for (const line of this.lines) { json.lines.push(line.getJson()); }

		localStorage.setItem("paperscope-calibration-"+id, JSON.stringify(json));
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CALIBRATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	displayControlPoints() {

		if(!this.calibrate) { return; }

		for (const surface of this.surfaces) {
			if(surface.isHidden) { continue; }
			surface.displayControlPoints();
		}

		for (const lineMap of this.lines) {
			lineMap.displayCalibration();
			lineMap.displayControlPoints();
		}
	}


	startCalibration() { this.calibrate = true; }
	stopCalibration() { this.calibrate = false; }
	toggleCalibration() { this.calibrate = !this.calibrate; }



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/

}

const pMapper = new ProjectionMapper();
export default pMapper;
