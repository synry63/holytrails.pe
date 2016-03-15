// backbone-overrides.js

//Backbone.Model Overrides
if( Backbone && Backbone.Model )
{
    Backbone.Model.prototype._super = function(funcName){
        if( funcName === undefined ) return null;
        return this.constructor.prototype[funcName].apply(this, _.rest(arguments) );
    };
    // nested models!  Might just override the internal representation of this...
    _.extend(Backbone.Model.prototype, {
      // Version of toJSON that traverses nested models
      toJSON: function() {
        var obj = _.clone(this.attributes);
        _.each(_.keys(obj), function(key) {
          if(!_.isUndefined(obj[key]) && !_.isNull(obj[key]) && _.isFunction(obj[key].toJSON)) {
            obj[key] = obj[key].toJSON();
          }
        });
        return obj;
      }
    });

    _.extend(Backbone.Collection.prototype, {
      // Version of toJSON that traverses nested models in collections
      toJSON: function() {
        return this.map(function(model){ return model.toJSON(); });
      }
    });
}
//Backbone.View Overrides
if( Backbone && Backbone.View )
{
    Backbone.View.prototype.eventDispatcher = _.extend({}, Backbone.Events);
}