/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import * as Cesium from 'cesium';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COLOR GENERATION
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Generates heatmap colors along a green-yellow-orange-red spectrum
	 * @param {number} value - The value to color
	 * @param {number} min - Minimum value in the dataset
	 * @param {number} max - Maximum value in the dataset
	 * @param {number} alpha - Alpha transparency (0-1)
	 * @returns {string} RGBA color string
	 */
	export function getHeatmapColor(value, min, max, alpha = 0.01) {

		if (min === max) {
			return `rgba(255, 165, 0, ${alpha})`; // Default to orange if all values are the same
		}

		// Normalize value between 0 and 1
		const normalized = (value - min) / (max - min);

		let r, g, b;

		if (normalized <= 0.33) {
			// Green to Yellow
			const t = normalized / 0.33;
			r = Math.floor(255 * t); // Add red component for yellow
			g = 255; // Keep green at maximum
			b = 0; // No blue
		} else if (normalized <= 0.66) {
			// Yellow to Orange
			const t = (normalized - 0.33) / 0.33;
			r = 255; // Keep red at maximum
			g = Math.floor(255 * (1 - t * 0.35)); // Reduce green gradually (255 to ~165)
			b = 0; // No blue
		} else {
			// Orange to Red
			const t = (normalized - 0.66) / 0.34;
			r = 255; // Keep red at maximum
			g = Math.floor(165 * (1 - t)); // Reduce green from orange level to 0
			b = 0; // No blue
		}

		// Ensure minimum alpha for better visibility
		const finalAlpha = Math.max(alpha, 0.2);

		return `rgba(${r}, ${g}, ${b}, ${finalAlpha})`;
	}


	/**
	 * Converts RGBA string to Cesium Color for 3D visualization
	 * @param {string} rgbaString - RGBA color string like "rgba(255, 0, 0, 0.5)"
	 * @returns {Object} Cesium Color object
	 */
	export function rgbaStringToCesiumColor(rgbaString) {

		// Parse RGBA string
		const match = rgbaString.match(/rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)/);
		if (!match) {
			return Cesium.Color.WHITE;
		}

		const r = parseInt(match[1]) / 255;
		const g = parseInt(match[2]) / 255;
		const b = parseInt(match[3]) / 255;
		const a = match[4] ? parseFloat(match[4]) : 1;

		return new Cesium.Color(r, g, b, a);
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	GEOJSON PROCESSING
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	/**
	 * Bakes heatmap colors directly into GeoJSON features properties
	 * @param {Object} features - GeoJSON features object
	 * @returns {Object} Processed GeoJSON with baked-in color properties
	 */
	export function bakeHeatmapColorsIntoGeoJSON(features) {

		if (!features || !features.features || features.features.length === 0) {
			return features;
		}

		// Extract values for normalization
		const values = features.features
			.map(feature => feature.properties.value)
			.filter(val => typeof val === 'number' && !isNaN(val));

		const minValue = values.length > 0 ? Math.min(...values) : 0;
		const maxValue = values.length > 0 ? Math.max(...values) : 1;

		// Sort features by value (largest to smallest) so smallest values render on top
		let sortedFeatures = [...features.features];
		sortedFeatures.sort((a, b) => {
			const valueA = a.properties.value || 0;
			const valueB = b.properties.value || 0;
			return valueB - valueA; // Descending order (largest first, smallest last/on top)
		});

		// Create a deep copy of the features and add color properties
		const processedFeatures = {
			...features,
			features: sortedFeatures.map(feature => {
				const processedFeature = {
					...feature,
					properties: { ...feature.properties }
				};

				// Calculate colors for this feature
				let fillColor = 'rgba(0, 255, 0, 0.01)'; // Default green color with higher alpha
				let strokeColor = 'rgba(0, 255, 0, 0.01)';

				if (feature.properties.value !== undefined) {
					const value = feature.properties.value;
					if (typeof value === 'number' && !isNaN(value)) {
						fillColor = getHeatmapColor(value, minValue, maxValue, 0.3);
						strokeColor = getHeatmapColor(value, minValue, maxValue, 0.5);
					}
				}

				// Bake colors into the feature properties
				processedFeature.properties._heatmap_fill_color = fillColor;
				processedFeature.properties._heatmap_stroke_color = strokeColor;
				processedFeature.properties._heatmap_value_property = 'value';
				processedFeature.properties._heatmap_value = feature.properties.value;

				return processedFeature;
			})
		};

		return processedFeatures;
	}



/*/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////*/
