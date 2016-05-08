<!DOCTYPE html>
<html lang="en">
	<head>
        <title>Chair 3D Model</title>
        <meta charset="utf-8">
    </head>

	<body>
		<script src="js/three.min.js"></script>

		<script src="js/MTLLoader.js"></script>
		<script src="js/OBJMTLLoader.js"></script>
		
		<!-- 鼠标控制相关 -->
		<script src="js/TrackballControls.js"></script>
		
		<script src="js/Detector.js"></script>
		<script src="js/stats.min.js"></script>

		<script>
			var container, stats;

			var camera, scene, renderer, projector, controls;

			var mouseX = 0, mouseY = 0;

			var windowHalfX = window.innerWidth / 2;
			var windowHalfY = window.innerHeight / 2;


			init();
			animate();


			function init() {

				container = document.createElement( 'div' );
				document.body.appendChild( container );

				camera = new THREE.PerspectiveCamera( 10, window.innerWidth / window.innerHeight, 1, 10000 );
				camera.position.z = 300;
				
				 
				//controls
				controls = new THREE.TrackballControls( camera );
				controls.rotateSpeed = 1.4;
				controls.zoomSpeed = 1.2;
				controls.panSpeed = 0.8;
				controls.noZoom = false;
				controls.noPan = false;
				controls.staticMoving = true;
				controls.dynamicDampingFactor = 0.3;

				// scene
				scene = new THREE.Scene();

				//lights    
				var light = new THREE.PointLight(0xffdddd);
				light.position.set(0,250,0);
				scene.add(light);
    
				var light2 = new THREE.PointLight(0xffdddd);
				light2.position.set(250,250,250);
				scene.add(light2);
				
				// model
				var loader = new THREE.OBJMTLLoader();
				loader.addEventListener( 'load', function ( event ) {

					var object = event.content;

					object.position.x = 0;
					object.position.y = -10;
					scene.add( object );

				});
				loader.load( "models/<?php echo $_GET["chairName"] ?>.obj", "models/<?php echo $_GET["chairName"] ?>.mtl" );

				// render
				projector = new THREE.Projector();
				renderer = new THREE.WebGLRenderer({antialias: true});
				renderer.setSize( window.innerWidth, window.innerHeight );
				container.appendChild( renderer.domElement );

				renderer.domElement.addEventListener('mousemove', onMouseMove);
				document.addEventListener('mousedown', onDocumentMouseDown, false);

				//

				window.addEventListener( 'resize', onWindowResize, false );

			}

			function onWindowResize() {

				windowHalfX = window.innerWidth / 2;
				windowHalfY = window.innerHeight / 2;

				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();

				renderer.setSize( window.innerWidth, window.innerHeight );

			}
			
			//picking
			function onDocumentMouseDown(event) {    
				event.preventDefault();
				var vector = new THREE.Vector3((event.clientX/window.innerWidth) * 2 - 1, -(event.clientY/window.innerHeight) * 2 + 1, 0.5);
				projector.unprojectVector(vector, camera);
				var raycaster = new THREE.Raycaster(camera.position, vector.sub(camera.position).normalize());
			}
	
			function onMouseMove( event ) {

				mouseX = event.clientX;
				mouseY = event.clientY;

			}

			function animate() {

				requestAnimationFrame( animate );
				render();

			}

			function render() {
			
				controls.update();
				camera.lookAt( scene.position );
				renderer.render( scene, camera );

			}

		</script>

	</body>
</html>
