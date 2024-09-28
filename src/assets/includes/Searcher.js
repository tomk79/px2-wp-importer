const FlexSearch = require("flexsearch").default;

module.exports = function(main){

	// --------------------------------------
	// FlexSearch インデックスの作成
	const index = new FlexSearch.Document({
		cache: true,
		encoder: "extra",
		tokenize: "full",
		preset: "match",
		document: {
			id: "id",
			store: [
				"t",
				"h2",
				"h3",
				"h4",
				"c",
			],
			index: [
				{ field: "t", boosting: 5 },
				{ field: "h2", boosting: 4 },
				{ field: "h3", boosting: 3 },
				{ field: "h4", boosting: 2 },
				{ field: "c", boosting: 1 },
			],
		},
	});

	this.setDocumentData = function(documentData){
		documentData.forEach((item, id) => {
			index.add(id, item);
		});
	}

	this.search = function(query, callback) {
		const origResults = index.search(query, {
			enrich: true,
			sort: true,
			bool: "or",
		});
		let done = {};
		let results = [];
		origResults.forEach((field)=>{
			field.result.forEach((item) => {
				if(done[item.id]){
					return;
				}
				done[item.id] = true;
				results.push(item);
			});
		});
		callback(results);
	}
}
