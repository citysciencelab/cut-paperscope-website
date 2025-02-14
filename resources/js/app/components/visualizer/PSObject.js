/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import * as Cesium from 'cesium';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CLASS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


class PSObject {

	constructor(feature, mapping) {

		this.uid = feature.properties.uid;
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
//	2D
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	get2D() {
		/*
		var feature = new Feature();
		const target = this.mapping.target;

		// geometry
		if(target == 'model') {
			const circle = new Circle(this.points[0], 0.00005);
			feature.setGeometry(circle);
		}
		else {
			const lastPoint = this.points[0];
			const polygon = new Polygon([[...this.points, lastPoint]]);
			feature.setGeometry(polygon);
		}

		// styling
		if(target == 'greenspace') {
			var fill = new Fill({color: '#DCF297'});
			var stroke = new Stroke({color: '#B6D397', width: 1});
		}
		if(target == 'street') {
			var fill = new Fill({color: '#D5D5D5'});
			var stroke = new Stroke({color: '#605D66', width: 1});
		}
		else {
			var fill = new Fill({color: this.getFillColor(false)});
			var stroke = new Stroke({color: this.getStrokeColor(false), width: 1});
		}

		feature.setStyle(new Style({fill, stroke}));
		return feature;
		*/

		return null;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	3D
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	get3D(scene) {

		switch(this.mapping.target) {
			case 'shape-2d':	return this.getShape2D(scene);
			case 'shape-3d':	return this.getShape3D(scene);
			case 'model':		return this.getModel(scene);
			case 'greenspace':	return this.getGreenspace(scene);
			case 'street':		return this.getStreet(scene);
			default:			return this.getShape3D(scene);
		}
	}


	getShape2D(scene) {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());
		const height = this.findHeight(scene, this.points);

		// entity
		var entity = new Cesium.Entity({
			id: this.uid,
			polygon: {
				hierarchy: new Cesium.PolygonHierarchy(positions),
				extrudedHeight: height + this.groundOffset,
				height: height - 5,
				material: this.getFillColor(true),
			}
		});

		return entity;
	}


	getShape3D(scene) {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());
		const height = this.findHeight(scene, this.points);

		// entity
		var entity = new Cesium.Entity({
			id: this.uid,
			polygon: {
				hierarchy: new Cesium.PolygonHierarchy(positions),
				extrudedHeight: parseInt(this.mapping.props?.height) + height,
				height: height - 5,
				material: this.getFillColor(true),
			},
		});

		return entity;
	}


	getModel(scene) {

		var uri = this.mapping.props.file;
		if(!uri.startsWith('http')) { uri = window.config.base_url + uri; }

		const height = this.findHeight(scene, this.points);

		// entity
		var entity = new Cesium.Entity({
			id: this.uid,
			position: Cesium.Cartesian3.fromDegrees(this.points[0][0], this.points[0][1], height),
			model: {
				uri,
				scale: this.mapping.props.scale,
				rotation: Cesium.Math.toRadians(this.mapping.props.rotation),
			}
		});

		return entity;
	}


	getGreenspace(scene) {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());
		const height = this.findHeight(scene, this.points);

		// entity
		var entity = new Cesium.Entity({
			id: this.uid,
			polygon: {
				hierarchy: positions,
				extrudedHeight: height + this.groundOffset,
				height: height - 5,
				material: Cesium.Color.fromBytes(210,232,141,255),
			}
		});

		return entity;
	}


	getStreet(scene) {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());
		const height = this.findHeight(scene, this.points);

		// entity
		var entity = new Cesium.Entity({
			id: this.uid,
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

		return entity;
	}


	findHeight(scene, points) {

		var maxHeight = 0;
		points.forEach(point => {
			const height = scene.globe.getHeight(Cesium.Cartographic.fromDegrees(point[0], point[1]));
			maxHeight = Math.max(maxHeight, height);
		});

		return maxHeight;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	getFillColor(is3D=true) {

		const hexColor = this.mapping.props?.fill;
		return is3D ? Cesium.Color.fromCssColorString(hexColor) : hexColor;
	}


	getStrokeColor(is3D=true) {

		const hexColor = this.mapping.props?.stroke;
		return is3D ? Cesium.Color.fromCssColorString(hexColor) : hexColor;
	}


	getRandomPointInsidePolygon() {

		const polygon = new Polygon([this.points]);
		const extent = polygon.getExtent();

		const pointInPolygon = false;

		while(!pointInPolygon) {

			var lat = extent[0] + Math.random() * (extent[2] - extent[0]);
			var lon = extent[1] + Math.random() * (extent[3] - extent[1]);

			if(polygon.intersectsCoordinate([lat, lon])) {
				return [lat, lon];
			}
		}
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/

}

export default PSObject;
