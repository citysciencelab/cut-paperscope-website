/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	INCLUDES
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	import fs from 'fs';
	import path from 'path';
	import { execSync } from 'child_process';

	import {parseSync} from '@loaders.gl/core';
	import {GLBLoader} from '@loaders.gl/gltf';
    
    import * as Cesium from 'cesium';



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	MAIN
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */

	
	var __dirname = path.resolve();
	
	const basePath = "https://daten-hamburg.de/gdi3d/datasource-data/LoD3_tex20cm_Area1/";
	const rootUrl = basePath + "tileset.json";
	const outputPath = __dirname + "/tileset-area1/";

	// iterate through all tilesets recursively
	searchTileset(rootUrl)



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	CRAWLER
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	async function searchTileset(url) {
		
		const tileset = await loadTileset(url);

		// get local path from url
		var path = outputPath + url.replace(basePath, "").replace("../../","");;

		// download tileset file
		var folder = path.split("/");
		folder.pop();
		folder = folder.join("/") + "/";
		if(!fs.existsSync(folder)) { fs.mkdirSync(folder, { recursive: true }); }
		fs.writeFileSync(path, JSON.stringify(tileset));

		// find nodes
		const rootNode = tileset.root;
		if(!rootNode) { console.error("No root node found in tileset."); return; }
		searchForFiles(rootNode);
	}


	async function loadTileset(url) {

		const response = await fetch(url);
		const data = await response.json();
		return data;
	}


	function searchForFiles(node) {

		if(node.content?.uri) {
			const url = basePath + node.content.uri;
			if(url.endsWith(".b3dm")) { downloadB3dmFile(url, node); }
			else if(url.endsWith(".json")) { searchTileset(url); }
		}

		if(node.children) {
			for(const child of node.children) { searchForFiles(child); }
		}
	}



/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	B3DM
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


	async function downloadB3dmFile(url, node) {
		
        //if(!url.includes("15/34587/6635.b3dm")) { return; }
        //if(!url.includes("16/69175/13271.b3dm")) { return; }
        //if(!url.includes("15/34587/6635.b3dm")) { return; }

		var parts = url.split("../../");
        
		// get relative folder name
		var folder = parts[1].split("/");
		var filename = folder.pop();
		folder = path.join(outputPath, folder.join("/")+"/");

		// create subfolder
		if(!fs.existsSync(folder)) { fs.mkdirSync(folder, { recursive: true }); }

		// download file
		const filePath = path.join(folder, filename);
		if(!fs.existsSync(filePath)) {

            try {
                var uri = url.replace("../../", "");
                console.log(`==> Downloading ${uri} ...`);
                execSync(`curl -o "${filePath}" "${uri}"`, { stdio: 'inherit' });
            } 
            catch (error) {
                console.error("Error during download:", error.message);
            }
        }

        // create bounding file
        const boundingPath = filePath.replace(".b3dm", "-bounding.json");
        //if(!fs.existsSync(boundingPath)) {
            await readB3dmFile(filePath,node);
        //}
	}


	async function readB3dmFile(fileb3dm, node) {

        // get b3dm file
		const url = "http://localhost/crawler/" + fileb3dm.split("geojson/").pop();
        const b3dmResource = new Cesium.Resource({url});
        console.log(`==> Reading ${fileb3dm} ...`);
        
        try {
            var arrayBuffer = fs.readFileSync(fileb3dm).buffer;
        }
        catch (error) {
            console.error("Error during reading b3dm file:", url);
            return;
        }

        // load b3dm content
        const loader = new Cesium.B3dmLoader({b3dmResource, arrayBuffer});
        await loader.load();

        // load gltf content
        var frameState = new Cesium.FrameState({id: 0});
        try {
            loader._gltfLoader.process(frameState);
            await Promise.all(loader._gltfLoader._loaderPromises);
        }
        catch (error) {
            console.error("Error during loading glTF content:", error.message);
            process.exit(1);
        }

        // save gltf json file
        const gltfJson = loader._gltfLoader._gltfJsonLoader._gltf
        //const gltfFile = fileb3dm.replace(".b3dm", ".gltf");
        //fs.writeFileSync(gltfFile, JSON.stringify(gltfJson));
        
        const loaders = await Promise.all(loader._gltfLoader._loaderPromises);
        var batchIds = [];
        const batchedVerts = {};
        const meshes = gltfJson.meshes;

        // iterate through all primitives
        for (let i = 0; i < meshes.length; i++) {

            // only visible meshes
            const attrs = meshes[i].primitives[0].attributes;
            if(!attrs['TEXCOORD_0']) { continue; }

            // find loader for batch ids
            var bf = loaders.find(l => l._accessorId == attrs['_BATCHID']);
            if(!bf) { console.error("No batch id found for mesh: ", i); continue; }
            var ta = bf._typedArray;
            var dv = new DataView(ta.buffer, ta.byteOffset, ta.byteLength);

            // set batch ids
            batchIds = [];
            for(let i = 0; i < ta.byteLength/4; i++) { batchIds.push(dv.getFloat32(i * 4, true)); }

            // find loader for vertices
            bf = loaders.find(l => l._accessorId == attrs['POSITION']);
            if(!bf) { console.error("No position found for mesh: ", i); continue; }
            ta = bf._typedArray;
            dv = new DataView(ta.buffer, ta.byteOffset, ta.byteLength);

            // set vertices
            const transformMat = bf._gltf.nodes[0].matrix;
            const rtcCenter = [ loader._transform[12], loader._transform[13], loader._transform[14] ]

            for (let j = 0; j < ta.byteLength/24; j++) {

                const offset = j * 24;
                const x = dv.getFloat32(offset, true);
                const y = dv.getFloat32(offset + 4, true);
                const z = dv.getFloat32(offset + 8, true);
                const id = batchIds[j];

                // convert from local to world coordinates
                let v = applyMatrix(transformMat,[x, y, z]);
                v[0] += rtcCenter[0];
                v[1] += rtcCenter[1];
                v[2] += rtcCenter[2];

                const latLon = ecefToLatLon(v[0], v[1], v[2]);

                // store by batch id
                if(!batchedVerts[id]) batchedVerts[id] = {
                    min: [latLon[0], latLon[1]],
                    max: [latLon[0], latLon[1]],
                }
                else {
                    if(latLon[0] < batchedVerts[id].min[0]) batchedVerts[id].min[0] = latLon[0];
                    if(latLon[1] < batchedVerts[id].min[1]) batchedVerts[id].min[1] = latLon[1];
                    if(latLon[0] > batchedVerts[id].max[0]) batchedVerts[id].max[0] = latLon[0];
                    if(latLon[1] > batchedVerts[id].max[1]) batchedVerts[id].max[1] = latLon[1];
                }
            }
        }


        // read batch ids
        // for (let i = 0; i < loader._gltfLoader._loaderPromises.length; i++) {
           
        //     const bf = await loader._gltfLoader._loaderPromises[i];

        //     if(bf._attributeSemantic === "_BATCHID" && bf._primitive.attributes["TEXCOORD_0"]) {

        //         batchIndex.push(bf._primitive.attributes["POSITION"]);
                
        //         const batchDataView = new DataView(bf._typedArray.buffer, bf._typedArray.byteOffset, bf._typedArray.byteLength);
        //         const length = bf._typedArray.byteLength/4;        
        
        //         for(let i = 0; i < length; i++) {
        //             const id = batchDataView.getFloat32(i * 4, true);
        //             batchIds.push(id);
        //             uniqueBatchIds.add(id);
        //         }
        //     }
        // }

       


        // for (let i = 0; i < loader._gltfLoader._loaderPromises.length; i++) {

        //     const bf = await loader._gltfLoader._loaderPromises[i];
            
        //     if(batchIndex.includes(bf._accessorId) && bf._primitive.attributes["TEXCOORD_0"] && bf._accessorId != bf._primitive.attributes["NORMAL"]) {

        //         // remove index
        //         batchIndex.splice(batchIndex.indexOf(bf._accessorId), 1);
        //         console.log("BatchIndex: ", batchIndex);
                
        //         const vertDataView = new DataView(bf._typedArray.buffer, bf._typedArray.byteOffset, bf._typedArray.byteLength);
        //         const transformMat = bf._gltf.nodes[0].matrix;
        //         const rtcCenter = [ loader._transform[12], loader._transform[13], loader._transform[14] ]
        //         const length = bf._typedArray.byteLength/24;
                
        //         for (let j = 0; j < length; j++) {

        //             const offset = j * 24;
        //             const x = vertDataView.getFloat32(offset, true);
        //             const y = vertDataView.getFloat32(offset + 4, true);
        //             const z = vertDataView.getFloat32(offset + 8, true);
        //             const id = batchIds[j];
        //             if(id == 89) { console.log("ID: ", id); }

        //             // convert from local to world coordinates
        //             let v = applyMatrix(transformMat,[x, y, z]);
        //             v[0] += rtcCenter[0];
        //             v[1] += rtcCenter[1];
        //             v[2] += rtcCenter[2];

        //             const latLon = ecefToLatLon(v[0], v[1], v[2]);

        //             // store by batch id
        //             if(!batchedVerts[id]) batchedVerts[id] = {
        //                 min: [latLon[0], latLon[1]],
        //                 max: [latLon[0], latLon[1]],
        //             }
        //             else {
        //                 if(latLon[0] < batchedVerts[id].min[0]) batchedVerts[id].min[0] = latLon[0];
        //                 if(latLon[1] < batchedVerts[id].min[1]) batchedVerts[id].min[1] = latLon[1];
        //                 if(latLon[0] > batchedVerts[id].max[0]) batchedVerts[id].max[0] = latLon[0];
        //                 if(latLon[1] > batchedVerts[id].max[1]) batchedVerts[id].max[1] = latLon[1];
        //             }
        //         }
        //     }
        // }


        //fs.writeFileSync("/Applications/MAMP/htdocs/features.json", JSON.stringify(batchedVerts));

        const boundingfile = fileb3dm.replace(".b3dm", "-bounding.json");
        fs.writeFileSync(boundingfile, JSON.stringify(batchedVerts));
	}


    
/*///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//
//	COORDINATE REFERENCE SYSTEMS
//
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////// */


    function applyMatrix(mat, v) {
        
        const x = v[0], y = v[1], z = v[2];
        
        // local transformation
        let vx = mat[0] * x + mat[4] * y + mat[8]  * z + mat[12];
        let vy = mat[1] * x + mat[5] * y + mat[9]  * z + mat[13];
        let vz = mat[2] * x + mat[6] * y + mat[10] * z + mat[14];
        
        // Correct Y-up to Z-up for 3d tiles coordinates (right-handed) 
        return [vx, -vz, vy];
    }


    /**
     * Convert ECEF coordinates to latitude and longitude.
     * @param {number} x - ECEF X coordinate (meters)
     * @param {number} y - ECEF Y coordinate (meters)
     * @param {number} z - ECEF Z coordinate (meters)
     * @returns {[number, number]} Latitude and longitude in degrees [lat, lon]
     */

    function ecefToLatLon(x, y, z) {

        // WGS84 ellipsoid constants
        const a = 6378137.0;                        // semi-major axis
        const f = 1 / 298.257223563;                // flattening
        const b = a * (1 - f);                      // semi-minor axis
        const e2 = 2 * f - f * f;                   // first eccentricity squared
        const ep2 = (a * a - b * b) / (b * b);      // second eccentricity squared

        const lon = Math.atan2(y, x);

        const p = Math.sqrt(x * x + y * y);
        const theta = Math.atan2(z * a, p * b);

        const sinTheta = Math.sin(theta);
        const cosTheta = Math.cos(theta);

        // Bowring's formula for latitude
        const lat = Math.atan2(
            z + ep2 * b * sinTheta * sinTheta * sinTheta,
            p - e2 * a * cosTheta * cosTheta * cosTheta
        );

        return [
            lat * 180 / Math.PI,
            lon * 180 / Math.PI
        ];
    }

