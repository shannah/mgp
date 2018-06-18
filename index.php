<?php
/*
 * Copyright (c) 2018 Web Lite Solutions Corp.
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

define('PAGE_URL', (isset($_SERVER['HTTPS']) ? "https" : "http") . "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]");
$mgpURL = PAGE_URL;
if (preg_match('/\/index.php.*$/', $mgpURL)) {
	$mgpURL = substr($mgpURL, 0, strpos($mgpURL, '/index.php'));
} else if ($mgpURL{strlen($mgpURL)-1} == '/') {
	$mgpURL = substr($mgpURL, 0, strlen($mgpURL)-1);
}
?><!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
    	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

		<!-- Place this tag in your head or just before your close body tag. -->
		<script async defer src="https://buttons.github.io/buttons.js"></script>
		<title>MGP: Maven (Github) Proxy</title>
		<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous"/>
		<style type="text/css">
			.snippet {
				display: none;
			}
			
			body.maven .snippet.maven, body.gradle .snippet.gradle, body.sbt .snippet.sbt {
				display:block;
			}
			
			#response {
				margin-top: 1em;
			}
			
			.instructions {
				display: none;
			}
			
			body.subartifact .instructions.subartifact, body.artifact .instructions.artifact {
				display: block;
			}
			
			.footer p {
				text-align:center;
				padding:0;
				margin:0
			}
		</style>
		<script
  src="https://code.jquery.com/jquery-3.3.1.min.js"
  integrity="sha256-FgpCb/KJQlLNfOu91ta32o/NMZxltwRo8QtmkMRdAu8="
  crossorigin="anonymous"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
		<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T" crossorigin="anonymous"></script>
		<script src="js/index.js"></script>
	</head>
	<body class="maven">
		<div class="container-fluid">
			<h1>MGP: Maven (Github) Proxy</h1>
		
			<p>Host Jars on GitHub and add them as dependencies in Maven and Gradle projects.</p>
		
			<form id="lookup-form">
			  <div class="form-group">
				<label for="repo-url">GitHub Repository URL</label>
				<input type="url" class="form-control" id="repo-url" aria-describedby="repo-url-help" placeholder="Enter Github repo URL" value="https://github.com/shannah/php4j">
				<small id="repo-url-help" class="form-text text-muted">The URL of the Github Repository where library is hosted</small>
			  </div>
			  <div class="form-group">
				<label for="artifact-id">Artifact ID</label>
				<input type="text" class="form-control" id="artifact-id" placeholder="" value="php4j-thin">
				<small id="artifact-id-help" class="form-text text-muted">If artifact is defined in subdirectory of repository, enter the artifact name here.  Leave blank to use artifact defined in repository root.</small>
				
			  </div>
			  <div class="form-group">
				<label for="version">Version</label>
				<input type="text" class="form-control" id="version" placeholder="master-SNAPSHOT" value="master-SNAPSHOT" aria-describedby="version-help" >
			  	<small id="artifact-id-help" class="form-text text-muted">Enter either tag name, commit sha, or branch name with '-SNAPSHOT' appended</small>
			  </div>
			  <button type="submit" class="btn btn-primary">Look Up</button>
			</form>
		
		
			<div id="response">
		
			</div>
		
			<div id="templates" style="display:none">
				<div class="template not-found">
					<div class="alert alert-danger" role="alert">Artifact  <span class="artifact-id">artifact</span> was not found</div>
				
					<div class="instructions subartifact">
						<p>To publish this artifact:</p>
						
						<p><strong>Step 1.</strong> Ensure that your Github repository contains the jar file that you would like to publish.</p>
						<p>e.g.</p>
						<pre><?php echo htmlentities(
							"$ git add ./bin/my-lib.jar\n" .
							"$ git commit -m 'Added my jar'\n" .
							"$ git push origin master");
						?></pre>
						
						<p><strong>Step 2.</strong> Add a directory named <span class="artifact-id">artifact</span> to the root of your repository.</p>
						
						<p><strong>Step 3.</strong> Add a pom.xml file inside your <span class="artifact-id">artifact</span> directory, describing the library and its dependencies.</p>
						<p>e.g.</p>
						<pre><?php echo htmlentities(
							'<project xmlns="http://maven.apache.org/POM/4.0.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' .
							'  xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 http://maven.apache.org/xsd/maven-4.0.0.xsd">'."\n" .
  							'  <modelVersion>4.0.0</modelVersion>'."\n" .
  							"  <properties>\n" .
						    "    <jar.path>../bin/my-lib.jar</jar.path>\n" .
  							"  </properties>\n" .
  							"  <groupId>{{groupId}}</groupId>\n" .
  							"  <artifactId>{{artifactId}}</artifactId>\n" .
  							"  <version>1.0-SNAPSHOT</version>\n" .
  							"  <packaging>jar</packaging>\n" .
 							"  <name>{{artifactId}}</name>\n" .	
 							"</project>\n");
						?></pre>
						<p><small class="text-muted">Note that the <code>&lt;groupId&gt;</code>, <code>&lt;artifactId&gt;</code>, and <code>&lt;version&gt;</code>
							tag contents will be ignored - you can put anything here as MGP will replace them with appropriate values for each request.</small></p>
						
						<p><strong>Step 4.</strong> Make sure the pom.xml file includes the <code>&lt;jar.path&gt;</code>
						tag containing the relative path to the jar file you added in step 1.</strong></p>
						
						<p>e.g.</p>
						<pre><?php echo htmlentities(
  							"  <properties>\n" .
						    "    <jar.path>../bin/my-lib.jar</jar.path>\n" .
  							"  </properties>\n");
						?></pre>
						
						<p><strong>How Versions Work</strong></p>
						
						<p>For the version field, you may specify any of</p>
						
						<ol>
							<li><strong>A git tag</strong>.  E.g. "v1.0"</li>
							<li><strong>A git branch</strong> (with "-SNAPSHOT" appended).  E.g. "master-SNAPSHOT"</li>
							<li><strong>A git commit hash</strong>.</li>
						</ol>
						
						
					</div>
					
					<div class="instructions artifact">
						<p>To publish this artifact:</p>
						
						<p><strong>Step 1.</strong> Ensure that your Github repository contains the jar file that you would like to publish.</p>
						<p>e.g.</p>
						<pre><?php echo htmlentities(
							"$ git add ./bin/my-lib.jar\n" .
							"$ git commit -m 'Added my jar'\n" .
							"$ git push origin master");
						?></pre>
						
						<p><strong>Step 2.</strong> Add a pom.xml in the repository root if it doesn't already exist</p>
						<p>e.g.</p>
						<pre><?php echo htmlentities(
							'<project xmlns="http://maven.apache.org/POM/4.0.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"' .
							'  xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 http://maven.apache.org/xsd/maven-4.0.0.xsd">'."\n" .
  							'  <modelVersion>4.0.0</modelVersion>'."\n" .
  							"  <properties>\n" .
						    "    <jar.path>bin/my-lib.jar</jar.path>\n" .
  							"  </properties>\n" .
  							"  <groupId>{{groupId}}</groupId>\n" .
  							"  <artifactId>{{artifactId}}</artifactId>\n" .
  							"  <version>1.0-SNAPSHOT</version>\n" .
  							"  <packaging>jar</packaging>\n" .
 							"  <name>{{artifactId}}</name>\n" .	
 							"</project>\n");
						?></pre>
						<p><small class="text-muted">Note that the <code>&lt;groupId&gt;</code>, <code>&lt;artifactId&gt;</code>, and <code>&lt;version&gt;</code>
							tag contents will be ignored - you can put anything here as MGP will replace them with appropriate values for each request.</small></p>
						
						<p><strong>Step 3.</strong> Make sure the pom.xml file includes the <code>&lt;jar.path&gt;</code>
						tag containing the relative path to the jar file you added in step 1.</strong></p>
						
						<p>e.g.</p>
						<pre><?php echo htmlentities(
  							"  <properties>\n" .
						    "    <jar.path>bin/my-lib.jar</jar.path>\n" .
  							"  </properties>\n");
						?></pre>
						
						<p><strong>How Versions Work</strong></p>
						
						<p>For the version field, you may specify any of</p>
						
						<ol>
							<li><strong>A git tag</strong>.  E.g. "v1.0"</li>
							<li><strong>A git branch</strong> (with "-SNAPSHOT" appended).  E.g. "master-SNAPSHOT"</li>
							<li><strong>A git commit hash</strong>.</li>
						</ol>
						
						
					</div>
				</div>
		
				<div class="template found">
					<div class="alert alert-success" role="alert">Artifact was found</div>
			
					<p>To get this artifact into your build:</p>
					
					
					
					<ul class="nav nav-tabs" data-id="addRepo" role="tablist">
					  <li class="nav-item">
						<a class="nav-link active snippet-toggle maven" href="#" >Maven</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link snippet-toggle gradle" href="#" >Gradle</a>
					  </li>
					  <li class="nav-item">
						<a class="nav-link snippet-toggle sbt" href="#" >SBT</a>
					  </li>
					</ul>
			
					<p><strong>Step 1.</strong> Add the MGP Repository to your build file</p>
			
					
			

						
					<div class="snippet maven tab-pane active" data-id="mavenAddRepo" role="tabpanel" aria-labelledby="mavenAddRepo-tab">
						<pre><?php echo htmlentities(
							"<repositories>\n" .
							"  <repository>\n" .
							"    <id>mgp</id>\n" .
							"    <url>$mgpURL</url>\n" .
							"  </repository>\n" .
							"</repositories>");
						?></pre>
					</div>
					<div class="snippet gradle tab-pane" data-id="gradleAddRepo" role="tabpanel" arial-labelledby="gradleAddRepo-tab">
						<p>Add it in your root build.gradle at the end of repositories:</p>
						<pre><?php echo htmlentities(
							"allprojects {\n" .
							"  repositories {\n" .
							"    ...\n" .
							"    maven { url '$mgpURL' }\n" .
							"  }\n" .
							"}");
						?></pre>
					</div>
			
					<div class="snippet sbt tab-pane" data-id="sbtAddRepo" role="tabpanel" arial-labelledby="sbtAddRepo-tab">
						<p>Add it in your build.sbt at the end of resolvers:</p>
						<pre><?php 
							echo htmlentities("resolvers += \"mgp\" at \"$mgpURL\"");
						?></pre>
					</div>
					
			
					<p><strong>Step 2.</strong> Add the dependency</p>
			

					<div class="snippet maven">
						<pre><?php echo htmlentities(
							"<dependency>\n" .
							"  <groupId>{{groupId}}</groupId>\n" .
							"  <artifactId>{{artifactId}}</artifactId>\n" .
							"  <version>{{version}}</version>\n" .
							"</dependency>");
						?></pre>
					</div>
					<div class="snippet gradle">
						<pre><?php echo htmlentities(
							"dependencies {\n" .
							"  implementation '{{groupId}}:{{artifactId}}:{{version}}'\n" .
							"}");
						?></pre>
					</div>
			
					<div class="snippet sbt">
						<pre><?php 
							echo htmlentities("libraryDependencies += \"{{groupId}}\" % \"{{artifactId}}\" % \"{{version}}\"");
						?></pre>
					</div>

				</div>
			</div>
			<div class="footer">
				<p><small>Created by <a href="http://sjhannah.com">Steve Hannah</a></small></p>
				<p><small>Read more about MGP on <a href="https://github.com/shannah/mgp">Github</a></small></p>
				<p><!-- Place this tag where you want the button to render. -->
<a class="github-button" href="https://github.com/shannah/mgp" data-size="large" aria-label="Star shannah/mgp on GitHub">Star</a></p>
			</div>
		</div>
		<a href="https://github.com/shannah/mgp"><img style="position: absolute; top: 0; right: 0; border: 0;" src="https://s3.amazonaws.com/github/ribbons/forkme_right_red_aa0000.png" alt="Fork me on GitHub"></a>
	</body>
</html>
