function ewpagecacheProcessHolePunchces(url, cacheKey, ruri) {
	new Ajax.Request(url, {
		method: 'get',
		evalJS: 'force',
		parameters: {
			id: cacheKey,
			ruri: ruri
		},
		onFailure: function() {
			
		}    
	});
}