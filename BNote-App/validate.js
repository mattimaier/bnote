validator = {
		getSpecialchars: function (){
			return "äüöÄÜÖß";
		},
		getElement: function(evt) {
			return evt.getSource();
		},
		getValue: function(element){
			return element.getValue();
		},
		
		street: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			var specialchars = validator.getSpecialchars();			
			var street = "^[\\\w." +  specialchars + ".\\\s\\\.\\\,\\\-\\\/\\\(\\\)]{1,100}$";
			
			 if (newvalue.match(street)) {
				 element.setValueState(sap.ui.core.ValueState.Success);
			 }
			 else {
				 element.setValueState(sap.ui.core.ValueState.Error);
			 }
		},
		
		zip: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			var zip = "^\\\d{4,6}$";
			if (newvalue.match(zip)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		city: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			var specialchars = validator.getSpecialchars();			
			var city = "^[\\\w." + specialchars + ".\\\s\\\.\\\,\\\-]{3,100}$";
			
			if (newvalue.match(city)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		phone: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			
			var phone = "^[0-9\\\+\\\-\\\/\\\s\\\(\\\)]{1,29}$";
			if (newvalue.match(phone)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		email: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			
			var email = "^[a-zA-Z0-9][\\\w\.-]*@(?:[a-zA-Z0-9][a-zA-Z0-9_-]+\.)+[A-Z,a-z]{2,5}$";
			if (newvalue.match(email)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		positive_amount: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			
			var positive_amount = /^\d{1,12}$/;
			if (newvalue.match(positive_amount)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		signed_amount: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			
			var signed_amount = /^-?\d{1,12}$/;
			if (newvalue.match(signed_amount)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		date: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			
			var date = /^\d{1,2}.\d{1,2}.\d{4}$/;
			if (newvalue.match(date)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		datetime: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			
			var datetime = /^\d{1,2}.\d{1,2}.\d{4}\ \d{2}:\d{2}$/;
			if (newvalue.match(datetime)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		name: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			var specialchars = validator.getSpecialchars();
			
			var name = "^[\\\w." + specialchars + ".\\\s\\\.\\\,\\\-\\\/\\\(\\\)]{1,100}$";
			if (newvalue.match(name)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		short_name: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			var specialchars = validator.getSpecialchars();
			
			var name = "^[\\\w." + specialchars + ".\\\s\\\.\\\,\\\-\\\/\\\(\\\)]{1,50}$";
			if (newvalue.match(short_name)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		subject: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			var specialchars = validator.getSpecialchars();
			
			var subject = "^[\\\w." + specialchars + ".\\\s\\\.\\\,\\\-\\\/\\\(\\\)]{1,255}$";
			if (newvalue.match(subject)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		money: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
						
			var money = /^-?\d{0,8},\d{0,2}$/;
			if (newvalue.match(money)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		time: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
						
			var time = /^\d{2}:\d{2}:\d{2}$/;
			if (newvalue.match(time)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		text: function(evt) {				
			element.setValueState(sap.ui.core.ValueState.Success);		
		},
		
		website_url: function(evt) {
			var element = validator.getElement(evt);
			var newvalue = validator.getValue(element);
			var website_url = /[-a-zA-Z0-9@:%_\+.~#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~#?&//=]*)?/gi;
			if (newvalue.match(website_url)) {
				element.setValueState(sap.ui.core.ValueState.Success);
			}
			else {			
				element.setValueState(sap.ui.core.ValueState.Error);
			}
		},
		
		
};