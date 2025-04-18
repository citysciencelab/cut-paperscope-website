/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import Draggable from './Draggable';
	import { getRandomizedColor } from '../helpers/helpers';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class Surface extends Draggable {


    constructor(id, w, h, res, type, buffer, pInst) {

		super(pInst, 0, 0);

        this.width = this.pInst.constrain(w, 0, this.pInst.width);
        this.height = this.pInst.constrain(h, 0, this.pInst.height);
        this.id = id;
        this.res = Math.floor(res);
        this.type = type;
		this.isHidden = false;

        this.controlPointColor = getRandomizedColor(this.id, this.type, this.pInst);

        this.buffer = buffer;
    }


    getMutedControlColor(col = this.controlPointColor) {

		return this.pInst.color(this.pInst.red(col), this.pInst.green(col), this.pInst.blue(col), 50);
    }



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	RENDERING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


    display(col = this.pInst.color('black')) {

		if(this.isHidden) { return; }

		this.buffer.background(col);
        this.displayTexture(this.buffer);
    }


    // override with geometry specifics
    displaySurface(isUV = true, tX = 0, tY = 0, tW = 1, tH = 1) {

		console.warn("should be overriding with specific geometry...");
    }


    displaySketch(sketch, tX = 0, tY = 0, texW = 0, texH = 0) {

		this.buffer.clear();
        this.buffer.push();

		// draw all textures from top left of surface
        sketch(this.buffer);
        this.buffer.pop();

        this.displayTexture(this.buffer, tX, tY, texW, texH);
    }


    displayTexture(tex, tX = 0, tY = 0, texW = 0, texH = 0) {

        if(!tex || tex.width <= 0 || tex.height <= 0) { return; }
		if(this.isHidden) { return; }

        if (texW <= 0) texW = tex.width;
        if (texH <= 0) texH = tex.height;
        const tW = tex.width / texW;
        const tH = tex.height / texH;

        this.pInst.push();
        this.pInst.noStroke();
        this.pInst.translate(this.x, this.y);
        this.pInst.textureMode(this.pInst.IMAGE);
        this.pInst.texture(tex);
        this.displaySurface(true, tX, tY, tW, tH);

        if (this.pInst.isCalibratingMapper()) {
            this.displayCalibration();
        }
        this.pInst.pop();
    }

    displayCalibration() {
        this.pInst.push();
        // TODO -
        // why translate??
        // to do with the way lines overlap in z dimension?
        // translate(0, 0, 3);
        this.displayOutline();
        this.pInst.pop();
    }

    displayOutline(col = this.controlPointColor) {
        this.pInst.strokeWeight(3);
        this.pInst.stroke(col);
        this.pInst.fill(this.getMutedControlColor());
        this.displaySurface(false);
    }


    isEqual(json) {
        return json.id === this.id && json.type === this.type;
    }

    getBounds(points) {
        let minX = Math.floor(Math.min(...points.map((pt) => pt.x)));
        let minY = Math.floor(Math.min(...points.map((pt) => pt.y)));
        let maxX = Math.floor(Math.max(...points.map((pt) => pt.x)));
        let maxY = Math.floor(Math.max(...points.map((pt) => pt.y)));

        return { x: minX, y: minY, w: maxX - minX, h: maxY - minY };
    }

    setDimensions(points) {
        const { w, h } = this.getBounds(points);
        this.width = w;
        this.height = h;
    }
}


export default Surface;
