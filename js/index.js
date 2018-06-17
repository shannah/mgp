(function() {
	var $ = jQuery;
	function checkArtifact(groupId, artifactId, version) {
		var complete = function(responseCode) {
		
		};
		var promise = {
			then : function(onComplete) {
				complete = onComplete;
			}
		};
	
		var url = groupId.split(/\./).join('/') +'/'+ artifactId + '/' + version + '/' + artifactId + '-' + version + '.pom';
		$.ajax(url)
			.always(function(xhr, textStatus) {
				complete(xhr.status);
			});

		
		return promise;
	}
	
	function parseArtifact(repoUrl, artifactId, version) {
		var out = {
			groupId: null,
			artifactId: null,
			version: null
		};
		repoUrl = repoUrl
			.replace(/^https:\/\/github.com\//, '')
			.replace(/\.git$/, '')
			;
		if (repoUrl.match(/^https?:\/\//)) {
			out.error = 'Only github URLs are supported';
			return out;
		}
		
		var parts = repoUrl.split(/\//);
		var userName = parts[0];
		var repoName = parts[1];
		
		if (artifactId == null || artifactId == repoName) {
			out.groupId = 'com.github.'+userName;
			out.artifactId = repoName;
		} else {
			out.groupId = 'com.github.'+userName+'.'+repoName;
			out.artifactId = artifactId;
		}
		out.version = version;
		return out;
		
	}
	
	function showNotFound(artifact) {
		$('#response').empty();
		$('#templates .template.not-found').clone().appendTo($('#response'));
	}
	
	function showFound(artifact) {
		console.log("Showing found");
		$('#response').empty();
		$('#templates .template.found').clone().appendTo($('#response'));
	}
	
	$(document).ready(function() {
		$('#search').click(function() {
			var artifact = parseArtifact($('#repo-url').val(), $('#artifact-id').val(), $('#version').val());
			if (artifact.error) {
				alert(artifact.error);
				return;
			}
			checkArtifact(artifact.groupId, artifact.artifactId, artifact.version)
				.then(function(responseCode) {
					if (responseCode == 404) {
						showNotFound();
					} else {
						showFound();
					}
				});
		});
	});
})();