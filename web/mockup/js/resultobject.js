function ResultObj(id) {

    this.id = id;

    this.setTitle = setTitle;
    function setTitle(title) {
	this.title = title;
    }

    this.setArtist = setArtist;
    function setArtist(artist) {
	this.artist = artist;
    }

    this.setYear = setYear;
    function setYear(year) {
	this.year = year;
    }

    this.setMaterial = setMaterial;
    function setMaterial(material) {
	this.material = material;
    }
    
    this.setPlace = setPlace;
    function setPlace(place) {
	this.place = place;
    }

    this.setCoord = setCoord;
    function setCoord(lat, lng) {
	this.coord = new OpenLayers.LonLat(lat, lng);
    }

    this.setModule = setModule;
    function setModule(module) {
	this.module = module;
    }

    this.setText = setText;
    function setText(text) {
	this.text = text;
    }

}