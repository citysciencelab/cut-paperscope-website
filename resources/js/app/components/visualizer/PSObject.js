/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	// Cesium
	import * as Cesium from 'cesium';

	// OpenLayers
	import Feature from 'ol/Feature';
	import Polygon from 'ol/geom/Polygon';
	import { Fill, Stroke, Style, Icon } from 'ol/style';
	import ImageLayer from 'ol/layer/Image';
	import Static from 'ol/source/ImageStatic';
	import * as olExtent from 'ol/extent';

	import proj4 from 'proj4';



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
			case 4: return "red";
			default: return "black";
		}
	}


	getMapping(m) {

		const defaultMapping = m.find(map => map.source == this.shape && map.color == 'all');
		const colorMapping = m.find(map => map.source == this.shape && map.color == this.color);

		return colorMapping ?? defaultMapping ?? null;
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	3D VISUALIZER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	create3dFeature() {

		switch(this.mapping.target) {
			case 'shape-2d':	return this.getShape2d();
			case 'shape-3d':	return this.getShape3d();
			case 'model':		return this.getModel();
			case 'greenspace':	return this.getGreenspace();
			case 'street':		return this.getStreet();
			default:			return this.getShape3d();
		}
	}


	getShape3d() {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());

		// entity
		const entity = this.createEntity({
			polygon: {
				hierarchy: new Cesium.PolygonHierarchy(positions),
				extrudedHeight: parseInt(this.mapping.props?.height),
				height: 0,
				material: this.getFillColor(false),
			},
		});
		this.getGroundHeight(entity, this.points, true);

		return entity;
	}


	getShape2d() {

		const positions = Cesium.Cartesian3.fromDegreesArray(this.points.flat());

		// entity
		const entity = this.createEntity({
			polygon: {
				hierarchy: new Cesium.PolygonHierarchy(positions),
				extrudedHeight: this.groundOffset,
				height: 0,
				material: this.getFillColor(false),
			}
		});
		this.getGroundHeight(entity, this.points);

		return entity;
	}


	getModel() {

		var uri = this.mapping.props.file;
		if(!uri?.startsWith('http')) { uri = window.config.base_url + uri; }

		// entity
		var entity = this.createEntity({
			position: Cesium.Cartesian3.fromDegrees(this.points[0][0], this.points[0][1], 0),
			orientation: Cesium.Transforms.headingPitchRollQuaternion(
				Cesium.Cartesian3.fromDegrees(this.points[0][0], this.points[0][1], 0),
				new Cesium.HeadingPitchRoll( Cesium.Math.toRadians(this.mapping.props.rotation || 0), 0, 0 )
			),
			model: {
				uri,
				scale: this.mapping.props.scale,
			},
		});
		this.getGroundHeight(entity, this.points);
		setTimeout(() => this.calculateModelBoundingRectangle(entity), 250);

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


	async getGroundHeight(entity, points, useMin = false) {

		const heights = await Cesium.sampleTerrain(this.map.terrainProvider, 11, points.map(p => Cesium.Cartographic.fromDegrees(p[0], p[1])));
		const minHeight = useMin ? Math.min(...heights.map(h => h.height)) : Math.max(...heights.map(h => h.height));
		if(entity.polygon) {
			entity.polygon.height = minHeight - 5;
			entity.polygon.extrudedHeight = minHeight + entity.polygon.extrudedHeight._value;
		}
		else if(entity.position) {

			const carto = Cesium.Cartographic.fromCartesian(entity.position._value);
			carto.height = minHeight;
			entity.position = Cesium.Cartesian3.fromRadians(carto.longitude, carto.latitude, carto.height);
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	2D VISUALIZER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	create2dFeature() {

		const target = this.mapping.target;

		// create geometry
		const points = this.shape == 'cross' ? this.getCrossPoints() : this.points;
		const lastPoint = points[0];
		const polygon = new Polygon([[...points, lastPoint]]);

		// create feature
		var feature = new Feature();
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
		else if(this.shape == 'cross') {
			var fill = new Fill({color: '#ff0000'});
			var stroke = new Stroke({color: '#ff0000', width: 2});
		}
		else {
			var fill = new Fill({color: this.getFillColor(true)});
			var stroke = new Stroke({color: this.getStrokeColor(true), width: 1});
		}

		feature.setStyle(new Style({fill, stroke}));
		return feature;
	}


	getCrossPoints() {

		const centerLon = this.points[0][0];
		const centerLat = this.points[0][1];

		// convert lat/long to EPSG:25832
		const epsg25832 = '+proj=utm +zone=32 +ellps=GRS80 +towgs84=0,0,0,0,0,0,0 +units=m +no_defs';
		const [centerX, centerY] = proj4('EPSG:4326', epsg25832, [centerLon, centerLat]);

		// convert points from svg to local coordinates
		const rawPoints = "5.8 5 8.2 2.7 7.3 1.8 5 4.2 2.7 1.8 1.8 2.7 4.2 5 1.8 7.3 2.7 8.2 5 5.8 7.3 8.2 8.2 7.3 5.8 5";
		const svgPoints = rawPoints.split(' ').map(v => parseFloat(v) - 5.0);

		// create geometry
		const scale = 3.0;
		const points = [];
		for(let i = 0; i < svgPoints.length; i += 2) {
			const x = centerX + svgPoints[i] * scale;
			const y = centerY - svgPoints[i + 1] * scale;
			const [lon, lat] = proj4(epsg25832, 'EPSG:4326', [x, y]);
			points.push([lon, lat]);
		}

		return points.length > 0 ? points : this.points[0];
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	getFillColor(is2D = true) {

		const hexColor = this.mapping.props?.fill;
		return is2D ? hexColor : Cesium.Color.fromCssColorString(hexColor);
	}


	getStrokeColor(is2D = true) {

		const hexColor = this.mapping.props?.stroke || this.mapping.props?.fill;
		return is2D ? hexColor : Cesium.Color.fromCssColorString(hexColor);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	HELPER 3D
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



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/

}

export default PSObject;
