/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import * as Cesium from 'cesium';
	import Feature from 'ol/Feature';
	import Polygon from 'ol/geom/Polygon';
	import { Fill, Stroke, Style, Icon } from 'ol/style';
	import ImageLayer from 'ol/layer/Image';
	import Static from 'ol/source/ImageStatic';
	import * as olExtent from 'ol/extent';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class PSObject {

	constructor(feature, map, mapping) {

		this.uid = feature.properties.uid;
		this.map = map;
		this.feature = feature;
		this.points = feature.geometry.coordinates;

		this.shape = this.getShapeType(feature.properties.shape);
		this.color = this.getColor(feature.properties.color);
		this.mapping = this.getMapping(mapping);

		this.groundOffset = 0.5;
	}


	getShapeType(index) {

		switch(index) {
			case 0: return "rectangle";
			case 1: return "circle";
			case 2: return "triangle";
			case 3: return "cross";
			case 4: return "organic";
			case 5: return "street";
			default: return "rectangle";
		}
	}

	getColor(colorIndex) {

		switch(colorIndex) {
			case 0: return "black";
			case 1: return "blue";
			case 2: return "green";
			case 3: return "yellow";
			default: return "black";
		}
	}


	getMapping(m) {

		if(this.shape == 'street') {
			return { source: 'street', color: 'all', target: 'street', props: {}, }
		};

		const defaultMapping = m.find(map => map.source == this.shape && map.color == 'all');
		const colorMapping = m.find(map => map.source == this.shape && map.color == this.color);

		return colorMapping ?? defaultMapping ?? null;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	3D VISUALIZER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	addEntity() {

		switch(this.mapping.target) {
			case 'shape-2d':	return this.getShape2D();
			case 'shape-3d':	return this.getShape3D();
			case 'model':		return this.getModel();
			case 'greenspace':	return this.getGreenspace();
			case 'street':		return this.getStreet();
			default:			return this.getShape3D();
		}
	}


	getShape3D() {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());
		const height = this.findHeight(this.points);

		// entity
		return this.createEntity({
			polygon: {
				hierarchy: new Cesium.PolygonHierarchy(positions),
				extrudedHeight: parseInt(this.mapping.props?.height) + height,
				height: height - 5,
				material: this.getFillColor(false),
			},
		});
	}


	getModel() {

		var uri = this.mapping.props.file;
		if(!uri?.startsWith('http')) { uri = window.config.base_url + uri; }

		const height = this.findHeight(this.points);

		// entity
		var entity = this.createEntity({
			position: Cesium.Cartesian3.fromDegrees(this.points[0][0], this.points[0][1], height),
			orientation: Cesium.Transforms.headingPitchRollQuaternion(
				Cesium.Cartesian3.fromDegrees(this.points[0][0], this.points[0][1], height),
				new Cesium.HeadingPitchRoll( Cesium.Math.toRadians(this.mapping.props.rotation || 0), 0, 0 )
			),
			model: {
				uri,
				scale: this.mapping.props.scale,
			},
		});

		return entity;
	}


	getGreenspace() {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());
		const height = this.findHeight(this.points);

		// entity
		return this.createEntity({
			polygon: {
				hierarchy: positions,
				extrudedHeight: height + this.groundOffset,
				height: height - 5,
				material: Cesium.Color.fromBytes(190,208,135,255),
			}
		});
	}


	getStreet() {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());
		const height = this.findHeight(this.points);

		// entity
		return this.createEntity({
			polygon: {
				hierarchy: positions,
				extrudedHeight: height + this.groundOffset,
				height: height - 5,
				material: Cesium.Color.DARKGRAY,
				outline: true,
    			outlineColor: Cesium.Color.DIMGRAY ,
    			outlineWidth: 4,
			}
		});
	}


	findHeight(points) {

		var maxHeight = 0;
		points.forEach(point => {
			const height = this.map.scene.globe.getHeight(Cesium.Cartographic.fromDegrees(point[0], point[1]));
			maxHeight = Math.max(maxHeight, height);
		});

		return maxHeight;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	2D VISUALIZER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	getShape2D() {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());
		const height = this.findHeight(this.points);

		// entity
		return this.createEntity({
			polygon: {
				hierarchy: new Cesium.PolygonHierarchy(positions),
				extrudedHeight: height + this.groundOffset,
				height: height - 5,
				material: this.getFillColor(false),
			}
		});
	}


	get2D() {

		const target = this.mapping.target;

		// For cross shape, return an image layer configuration instead of a feature
		if (this.shape == 'cross') {
			return this.getCrossImageLayer();
		}

		var feature = new Feature();

		// geometry for other shapes
		const lastPoint = this.points[0];
		const polygon = new Polygon([[...this.points, lastPoint]]);
		feature.setGeometry(polygon);

		// styling
		if(target == 'greenspace') {
			var fill = new Fill({color: '#DCF297'});
			var stroke = new Stroke({color: '#B6D397', width: 1});
		}
		else if(target == 'street') {
			var fill = new Fill({color: '#D5D5D5'});
			var stroke = new Stroke({color: '#605D66', width: 1});
		}
		else {
			var fill = new Fill({color: this.getFillColor(true)});
			var stroke = new Stroke({color: this.getStrokeColor(true), width: 1});
		}

		feature.setStyle(new Style({fill, stroke}));
		return feature;
	}


	getCrossImageLayer() {

		const [cx, cy] = this.points[0];
		const baseScale = 0.00003;
		const referenceWidth = 1920;
		const scale = baseScale * (referenceWidth / window.innerWidth);

		// Adjust longitude buffer for latitude
		const latRad = cy * Math.PI / 180;
		const lonBuffer = scale / Math.cos(latRad);
		const latBuffer = scale;

		// Create an extent that preserves aspect ratio
		const extent = [
			cx - lonBuffer, cy - latBuffer,
			cx + lonBuffer, cy + latBuffer
		];

		const fillColor = this.getFillColor(true);
		const originalSvgUrl = window.config.base_url + 'svg/app/cross.svg';

		// Fetch the SVG content and replace the color
		return fetch(originalSvgUrl)
			.then(response => response.text())
			.then(svgContent => {
				// Replace any fill color with the dynamic color using regex
				let coloredSvgContent = svgContent.replace(/fill="[^"]*"/g, `fill="${fillColor}"`);
				coloredSvgContent = coloredSvgContent.replace(/fill:\s*#[0-9a-fA-F]{3,6}/g, `fill: ${fillColor}`);

				// Create blob URL for the modified SVG
				const blob = new Blob([coloredSvgContent], { type: 'image/svg+xml' });
				const svgUrl = URL.createObjectURL(blob);

				// Create and return the image layer
				return new ImageLayer({
					source: new Static({
						url: svgUrl,
						projection: 'EPSG:4326',
						imageExtent: extent,
					}),
					opacity: 1,
					// Store metadata for identification
					crossObject: true,
					objectId: this.uid
				});
			})
			.catch(error => {
				console.error('Error loading cross SVG:', error);
			});
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	createEntity(properties) {

		// update existing entity
		var entity = this.map.entities.getById(this.uid);
		if(entity) { return entity; }

		// create new entity
		entity = new Cesium.Entity({
			id: this.uid,
			...properties
		});

		entity.boundingRectangle = this.calculatBoundingRectangle();

		this.map.entities.add(entity);

		return entity;
	}


	calculatBoundingRectangle() {

		// find min and max coordinates from points
		const minX = Math.min(...this.points.map(p => p[0]));
		const maxX = Math.max(...this.points.map(p => p[0]));
		const minY = Math.min(...this.points.map(p => p[1]));
		const maxY = Math.max(...this.points.map(p => p[1]));

		return Cesium.Rectangle.fromDegrees(minX, minY, maxX, maxY);
	}


	calculateModelBoundingRectangle(entity) {

		// check for bounding sphere
		var boundingSphere = new Cesium.BoundingSphere();
		const result = this.map.dataSourceDisplay.getBoundingSphere(entity,true, boundingSphere);

		// retry check
		if(result != Cesium.BoundingSphereState.DONE) {
			setTimeout(() => this.calculateModelBoundingRectangle(entity), 250);
			return;
		}

		// sphere to rectangle
		const rectangle = Cesium.Rectangle.fromBoundingSphere(boundingSphere);
		entity.boundingRectangle = rectangle;
	}


	getFillColor(is2D = true) {

		const hexColor = this.mapping.props?.fill;
		return is2D ? hexColor : Cesium.Color.fromCssColorString(hexColor);
	}


	getStrokeColor(is2D = true) {

		const hexColor = this.mapping.props?.stroke || this.mapping.props?.fill;
		return is2D ? hexColor : Cesium.Color.fromCssColorString(hexColor);
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/

}

export default PSObject;
