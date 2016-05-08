<!DOCTYPE html>
<html lang="gbk">
	<head>
		<title>座椅三维重建</title>
		<meta charset="gbk">
		<meta name="viewport" content="width=device-width, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0">
		<style>
			body{
				background-color: #000000;
				color: #ffffff;
			}
			button{
				color: green;
				font-weight: bold;
			}
		</style>
		<script src="js/three.min.js"></script>
		<script src="js/stats.min.js"></script>
		<script>
			var segArray  = new Array();
			var quadArray = new Array();
			var ovalArray = new Array();
			var segCount;
			var quadCount;
			var ovalCount;
		
			var drawStyle = "seg";
			
			function drawSegment(){
				drawStyle = "seg";
			}
			
			function drawQuad(){
				drawStyle = "quad";
			}
			
			function drawOval(){
				drawStyle = "oval";
			}
			
			function gen3d(){
				window.location = "recognition.php?seg="+encodeURIComponent(segArray)+"=quad="+encodeURIComponent(quadArray)+"=oval="+encodeURIComponent(ovalArray);
			}
		</script>
	</head>
	<body>
		<script>
			var container;
			var camera;
			var scene;
			var renderer;
			var projector;
			var target = new THREE.Vector3( 0, 0, 0 );
			var mouseDownCount;
			var segMouseClick;
			var ovalMouseClick;
			
			var segPoint1  = new THREE.Vector3();
			var segPoint2  = new THREE.Vector3();
			var quadPoint1 = new THREE.Vector3();
			var quadPoint2 = new THREE.Vector3();
			var quadPoint3 = new THREE.Vector3();
			var quadPoint4 = new THREE.Vector3();
			var ovalPoint1 = new THREE.Vector3();
			var ovalPoint2 = new THREE.Vector3();
			
			var geometry;
			var material;


			init();
			animate();

			function init() {
			
				segCount  = 0;
				quadCount = 0;
				ovalCount = 0;

				container = document.createElement( 'div' );
				document.body.appendChild( container );
				var info = document.createElement('div');
				info.style.position = 'absolute';
				info.style.top = '10px';
				info.style.width = '100%';
				info.style.textAlign = 'center';
				info.innerHTML = '<button onclick=\'drawSegment();\'>线段</button>'
				+'&nbsp;&nbsp;&nbsp;<button onclick=\'drawQuad();\'>四边形</button>'
				+'&nbsp;&nbsp;&nbsp;<button onclick=\'drawOval();\'>椭圆</button>'
				+'&nbsp;&nbsp;&nbsp;<button onclick=\'gen3d();\'>生成3D模型</button>';
				container.appendChild( info );

				camera = new THREE.PerspectiveCamera( 10, window.innerWidth / window.innerHeight, 1, 100 );
				camera.position.x = 0;
				camera.position.y = 0;
				camera.position.z = 100;
				camera.lookAt( target );

				scene = new THREE.Scene();

				mouseDownCount = 0;
				segMouseClick  = 0;
				ovalMouseClick = 0;
				
				geometry = new THREE.Geometry();
				material = new THREE.LineBasicMaterial( { color: 0xffffff, opacity: 1 } );
			
				projector = new THREE.Projector();
				
				renderer = new THREE.CanvasRenderer();
				renderer.setSize( window.innerWidth, window.innerHeight );
				container.appendChild(renderer.domElement);

				document.addEventListener( 'mousedown', onDocumentMouseDown, false);
				
				window.addEventListener( 'resize', onWindowResize, false );

			}

			function onDocumentMouseDown( event ){
				
				if( event.clientY < 40 ){
					return;
				};
				
				event.preventDefault();	
				var mouse = new THREE.Vector3();
				mouse.x = (event.clientX-7)*2/window.innerWidth - 1;
				mouse.y = -((event.clientY-7)*2)/window.innerHeight + 1;
				mouse.z = 0.5;
				projector.unprojectVector(mouse, camera);
				//var dir = mouse.sub(camera.position).normalize();
				//var ray = new THREE.Raycaster(camera.position,dir);
				//var distance = - camera.position.z / dir.z;
				//var pos = camera.position.clone().add(dir.multiplyScalar(distance));
				//var mx = MX/4*100;
				//var my = MY/4*100;
				//alert(mx);
				
				if( drawStyle == "quad" ){
					segMouseClick  = 0;
					ovalMouseClick = 0;
					quadArray.push(event.clientX);
					quadArray.push(event.clientY);
					switch(mouseDownCount){
						case 0:
							quadPoint1 = mouse;
							mouseDownCount += 1;
							break;
						case 1:
							quadPoint2 = mouse;
							mouseDownCount += 1;
							geometry.vertices.push(quadPoint1);
							geometry.vertices.push(quadPoint2);
							break;
						case 2:
							quadPoint3 = mouse;
							mouseDownCount += 1;
							geometry.vertices.push(quadPoint2);
							geometry.vertices.push(quadPoint3);
							break;
						case 3:
							quadPoint4 = mouse;
							geometry.vertices.push(quadPoint3);
							geometry.vertices.push(quadPoint4);
							geometry.vertices.push(quadPoint4);
							geometry.vertices.push(quadPoint1);
							mouseDownCount = 0;
							break;
						default:
							break;
					
					}
					//alert(event.clientX+" "+event.clientY);
					//geometry.vertices.push( new THREE.Vector3( vector.sub(camera.position).normalize().x, vector.sub(camera.position).normalize().y, 0 ) );
					if( geometry.vertices.length > 0 ){
						var line = new THREE.Line( geometry, material );
						line.type = THREE.LinePieces;
						scene.add( line );
					}
				}
				
				else if( drawStyle == "seg" ){
				
					segArray.push(event.clientX);
					segArray.push(event.clientY);
					
					mouseDownCount = 0;
					OvalMouseClick = 0;
					
					if( segMouseClick == 0 ){
						segMouseClick = 1;
						segPoint1 = mouse;
					}
					else{
						segPoint2 = mouse;
						segMouseClick = 0;
						geometry.vertices.push(segPoint1);
						geometry.vertices.push(segPoint2);
						var line = new THREE.Line( geometry, material );
						line.type = THREE.LinePieces;
						scene.add( line );
					}
				}
				
				else if( drawStyle == "oval" ){
				
					ovalArray.push(event.clientX);
					ovalArray.push(event.clientY);
				
					segMouseClick  = 0;
					mouseDownCount = 0;
					
					if( ovalMouseClick == 0 ){
						ovalMouseClick = 1;
						//ovalPoint1 = mouse;
						ovalPoint1.x = event.clientX;
						ovalPoint1.y = event.clientY;
					}
					
					else{ 
						//ovalPoint2 = mouse;
						ovalPoint2.x = event.clientX;
						ovalPoint2.y = event.clientY;
						ovalMouseClick = 0;
						var ellipse = new THREE.EllipseCurve( 
							(ovalPoint1.x + ovalPoint2.x) / 2.0, 
							(ovalPoint1.y + ovalPoint2.y) / 2.0 ,
							Math.abs((ovalPoint1.x - ovalPoint2.x)/2.0), 
							Math.abs((ovalPoint1.y - ovalPoint2.y)/2.0),
							0, 
							2.0 * Math.PI, 
							false
						);
						var ovalPoints = ellipse.getPoints(300);
						var oval3DPoints = new Array();;
						for(var i=0; i<300; i++){
							var oval3DPoint = new THREE.Vector3();
							oval3DPoint.x = (ovalPoints[i].x-7)*2/window.innerWidth - 1;
							oval3DPoint.y = -(ovalPoints[i].y*2-7)/window.innerHeight + 1;
							oval3DPoint.z = 0.5;
							projector.unprojectVector(oval3DPoint, camera);
							oval3DPoints.push(oval3DPoint);
						}
						for(var i=1; i<300; i++){
							geometry.vertices.push(oval3DPoints[i-1]);
							geometry.vertices.push(oval3DPoints[i]);
						}
						geometry.vertices.push(oval3DPoints[299]);
						geometry.vertices.push(oval3DPoints[0]);
						
						var line = new THREE.Line( geometry, material );
						line.type = THREE.LinePieces;
						scene.add( line );
						
						/*var ellipsePath = new THREE.CurvePath();
						ellipsePath.add( ellipse );
						var ellipseGeometry = ellipsePath.createPointsGeometry(100);
						ellipseGeometry.computeTangents();
						var line = new THREE.Line(ellipseGeometry, material);
						scene.add( line );*/
					}
				}
			}
			
			function onWindowResize() {

				camera.aspect = window.innerWidth / window.innerHeight;
				camera.updateProjectionMatrix();
				renderer.setSize( window.innerWidth, window.innerHeight );

			}

			function animate() {

				requestAnimationFrame( animate );
				render();

			}

			function render() {

				renderer.render( scene, camera );

			}

		</script>

	</body>
</html>