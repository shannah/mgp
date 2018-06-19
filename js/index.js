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
		
		if (!artifactId || artifactId == repoName) {
			out.groupId = 'com.github.'+userName;
			out.artifactId = repoName;
		} else {
			out.groupId = 'com.github.'+userName+'.'+repoName;
			out.artifactId = artifactId;
		}
		out.version = version;
		return out;
		
	}
	
	function showTemplate(name, artifact) {
		//console.log("Showing found");
		$('#response').empty();
		var tpl = $('#templates .template.'+name).clone();
		$('[data-id]', tpl).each(function() {
			$(this).attr('id', $(this).attr('data-id'));
		});
		
		decorate(tpl, artifact);
		tpl.appendTo($('#response'));
	}
	
	function showNotFound(artifact) {
		showTemplate('not-found', artifact);
	}
	
	function showFound(artifact) {
		showTemplate('found', artifact);
	}
	
	function decorate(root, artifact) {

		$('.link-to-instructions', root).click(function() {
			$('.link-to-instructions-input', root)
				.val(
						$('body').attr('data-mgp-url') + 
						'?q='+encodeURIComponent(artifact.groupId) + 
						":"+encodeURIComponent(artifact.artifactId) + 
						":"+encodeURIComponent(artifact.version)
				)
				.fadeIn(function() {
				
					$(this).select();
				}
			);
			return false;
		});

	
		$('.snippet-toggle', root).click(function() {
			var type = $(this).hasClass('gradle') ? 'gradle' :
				$(this).hasClass('sbt') ? 'sbt' : 'maven';
				
			$('body').removeClass('maven').removeClass('gradle').removeClass('sbt')
				.addClass(type);
			$('.snippet-toggle').removeClass('active');
			$('.snippet-toggle.'+type).addClass('active');

			return false;
		});
		$('body').removeClass('artifact').removeClass('subartifact');
		if (artifact) {
			console.log(artifact);
			$('span.artifact-id', root).text(artifact.artifactId);
			$('pre', root).each(function() {
				var text = $(this).text();
				text = text.replace(/\{\{groupId\}\}/g, artifact.groupId);
				text = text.replace(/\{\{artifactId\}\}/g, artifact.artifactId);
				text = text.replace(/\{\{version\}\}/g, artifact.version);
				$(this).text(text);
			});
			
			if (artifact.groupId.split(/\./).length  == 3) {
				$('body').addClass('artifact');
			} else {
				$('body').addClass('subartifact');
			}
		}
	}
	
	$(document).ready(function() {
		$('#find-artifact').click(function() {
			$('body').removeClass('lookup-hidden').addClass('lookup-visible');
			setTimeout(function(){$('#repo-url').select();}, 100);
			

		});
		
	
		$('#lookup-form').submit(function() {
			var artifact = parseArtifact($('#repo-url').val(), $('#artifact-id').val(), $('#version').val());
			if (artifact.error) {
				alert(artifact.error);
				return false;
			}
			checkArtifact(artifact.groupId, artifact.artifactId, artifact.version)
				.then(function(responseCode) {
					if (responseCode == 404) {
						showNotFound(artifact);
					} else {
						showFound(artifact);
					}
				});
			return false;
		});
		
		if ($('body').hasClass('lookup-hidden')) {
			$('#lookup-form').submit();
		}
	});
})();