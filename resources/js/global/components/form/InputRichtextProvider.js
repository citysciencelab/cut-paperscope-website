export default {

	previewsInData: true,
	extraProviders: [

		// Spotify
		{
			name: 'spotify',
			url: [ /^open\.spotify\.com.*\/(track|episode)\/(\w+)/],
			html: match => {
				const type = match[1];
				const id = match[2];
				return (
					`<iframe src="https://open.spotify.com/embed/${type}/${id}" ` +
						'style="position: absolute; width: 100%; height: 100%; top: 0; left: 0;" ' +
						'title="Spotify audio player" ' +
						'frameborder="0" allowtransparency="true" allow="encrypted-media">' +
					'</iframe>'
				);
			}
		},
		// Youtube livestreams
		{
			name: 'youtube-live',
			url: [ /^www\.youtube\.com\/live\/(\w+)/],
			html: match => {
				const id = match[1];
				return (
					'<div style="position:relative; width:100%; height:0; padding-bottom: 57%;">' +
						`<iframe src="https://www.youtube.com/embed/${id}" ` +
							'style="position:absolute; width:100%; height:100%; top:0; left:0;" ' +
							'title="YouTube video player" ' +
							'allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" ' +
							'allowfullscreen>' +
						'</iframe>' +
					'</div>'
				);
			}
		},
	]
};
