//Override Underscore templates settings to prevent errors when asp_tags=on

if( typeof _ !== 'undefined' && _.templateSettings )
{
    _.templateSettings = {
        escape: /\{\{([^\}]+?)\}\}(?!\})/g,
        evaluate: /<#([\s\S]+?)#>/g,
        interpolate: /\{\{\{([\s\S]+?)\}\}\}/g
    };
}


/* USEFUL PROTOTYPES */

/**
 * courtesy from: http://monocleglobe.wordpress.com/2010/01/12/everybody-needs-a-little-printf-in-their-javascript/
 */
if( !String.prototype.printf )
{
	String.prototype.printf = function (obj) {
		var useArguments = false;
		var _arguments = arguments;
		var i = -1;
		if (typeof _arguments[0] == "string") {
			useArguments = true;
		}
		if (obj instanceof Array || useArguments) {
			return this.replace(/\%s/g,
				function (a, b) {
					i++;
					if (useArguments) {
						if (typeof _arguments[i] == 'string') {
							return _arguments[i];
						}
						else {
							throw new Error("Arguments element is an invalid type");
						}
					}
					return obj[i];
				});
		}
		else {
			return this.replace(/{([^{}]*)}/g,
				function (a, b) {
					var r = obj[b];
					return typeof r === 'string' || typeof r === 'number' ? r : a;
				});
		}
	};
}

if( !String.prototype.insertAtIndex ){
    String.prototype.insertAtIndex = function (index, string) {
        var ind = index < 0 ? this.length + index  :  index;
        return  this.substring(0, ind) + string + this.substring(ind, this.length);
    };
}
