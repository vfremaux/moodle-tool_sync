function selectcourses(courses, courseselection){

	selection = courses.selectedIndex;
	if(selection != -1){
		while(courseselection.selectedIndex != -1){
			courseselection.options[courseselection.selectedIndex].selected = false;
		}
		while(courses.selectedIndex > -1){
			if(courses.options[courses.selectedIndex].value == "Id_type_bien"){
				courses.options[courses.selectedIndex] = null;
				courses.form.Id_categorie_bien.options[0].select= true;
			} else {
				//on cherche la place de notre champ
				for(place = 0 ; place < courseselection.length ; place++){
					if(courseselection.options[place].text > courses.options[courses.selectedIndex].text){
						break;
					}
				}
				for(i = courseselection.length ; i > place ; i--){
					courseselection.options[i] = new Option(courseselection.options[(i-1)].text,courseselection.options[(i-1)].value);
				}

				courseselection.options[place] = new Option(courses.options[courses.selectedIndex].text,courses.options[courses.selectedIndex].value);
				courses.options[courses.selectedIndex] = null;
				courseselection.options[place].selected = true;
			}
		}
		if(courses.length > 0){
			if(selection >= courses.length ){
				selection = courses.length-1;
			}
			courses.options[selection].selected = true;
		}
	}
}

function select_all(frm){
	for(i = 0 ; i < frm.courselist.length ; i++){
		frm.courselist.options[i].selected = false;
	}
	frm.courselist.name = "liste_champs[]";
	for(i = 0 ; i < frm.selection.length ; i++){
		frm.selection.options[i].selected = true;
	}
	frm.selection.name = "selection[]";
}

function priorite_champ(selection,mode){
	if(selection.length < 2 ){
		return;
	}
	old_place = selection.selectedIndex;
	if(mode == 'up' && old_place > 0){
		new_place = old_place - 1;
	}else if(mode == 'down' && old_place < selection.length-1){
		new_place = old_place+1;
	}
	tmp = new Option(selection.options[new_place].text,selection.options[new_place].value);
	selection.options[new_place] = new Option(selection.options[old_place].text,selection.options[old_place].value);
	selection.options[old_place] = new Option(tmp.text,tmp.value);
	selection.options[new_place].selected = true;
}