// wizard handler(s) here
function launchWizard( name )
{
	var d = _findObj('divWizardPoint');

	// table object
	var obj = _findObj('tblMain');
	//DumperPopup(obj);

	// remove any existing wizards
	clearWizard( );

	var div = document.createElement('div');
	div.name = 'divWizard';
	div.id = div.name;

	var x = findPosX(obj);
	var y = findPosY(obj);

	div.style.top = y + 'px';
	div.style.left = x + 'px';
	div.style.width = "100%";
	div.style.height = obj.scrollHeight + "px";
	div.style.position = "absolute";
	div.style.backgroundColor = '#FFFFFF';

	div.innerHTML = "Loading wizard...";
	d.appendChild(div);

	fetchWizard(div, name);
}

function fetchWizard( o, name )
{
	var d = new AJAX_Data( );

	d.add('a', 'wizard');
	d.add('m', name);
	
	aj_queue.add(interface_page, d, onFetchWizardLoad, o);
}

function onFetchWizardLoad( data, o )
{
	if(data.error)
	{
		o.innerHTML = data.raw;
		return;
	}

	o.innerHTML = data.get('content');
}

function clearWizard( )
{
	var dOld = _findObj('divWizard');
	var d = _findObj('divWizardPoint');

	try
	{
		//alert(d + ' : ' + dOld);
		if(dOld && d)
		{
			dOld.style.visibility = "hidden";
			// remove an old 'wizards' on the page
			d.removeChild(dOld);
			//return;
		}
	}
	catch(e)
	{
		// nothing
		//DumperPopup(e);
		//alert(e.message);
	}
}
