$(function(){

	var __PDF_DOC,
		__CALENDAR_MONTH = ['Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'],
		__CALENDAR_DAY = [1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17,18,19,20,21,22,23,24,25,26,27,28,29,30,31],
		__CALENDAR_YEAR = [2020,2021,2022],
		__TIPO_FORMACION = ['Distancia','Teleformación','Mixta'],
		__TIPO_PAGO = ['Único','Recurrente'],
		__CURRENT_PAGE,
		__CURRENT_DOC,
		__TOTAL_PAGES,
		__PAGE_RENDERING_IN_PROGRESS = 0,
		__CANVAS = $('#pdf-canvas').get(0),
		__CANVAS_CTX = __CANVAS.getContext('2d'),
		__DOCS_PATH = '/static/documents/',
		__INPUTS = {
			'CCF_017': {
				1: [
					{
						name: 'alumno_1',
						title: 'Nombre del alumno o texto de bienvenida',
						value: 'Le damos la bienvenida a la formación continua',
						placeholder: 'Le damos la bienvenida a la formación continua',
						maxlength: 80,
						align: 'C',
						y: 25
					},
					{
						name: 'telefono',
						title: 'Nro. de teléfono',
						placeholder: '93 866 35 23',
						value: '93 866 35 23',
						maxlength: 16,
						x: 58,
						y: 52,
						width: 42.5
					},
					{
						name: 'email',
						title: 'Email de contacto',
						placeholder: 'tutor@gdf-formacion.com',
						value: 'tutor@gdf-formacion.com',
						maxlength: 50,
						x: 47,
						y: 55.25,
						width: 53.5
					},
					{
						name: 'horario',
						title: 'Horario de tutorías',
						placeholder: 'de 9 a 14 de Lunes a Viernes',
						value: 'de 9 a 14 de Lunes a Viernes',
						maxlength: 50,
						x: 50,
						y: 58.35,
						width: 50.5
					},
					{
						name: 'direccion',
						title: 'Dirección',
						placeholder: 'Ctra. de Granollers a Carcadeu K.1.5 of. 4 - 08520 Les Franqueses del Vallès',
						value: 'Ctra. de Granollers a Carcadeu K.1.5 of. 4 - 08520 Les Franqueses del Vallès',
						align: 'C',
						y: 69.5,
						x: 1
					}
				],
				2:[
					{
						name: 'empresa',
						title: 'Nombre de la empresa',
						placeholder: 'Empresa',
						maxlength: 32,
						x: 30,
						y: 57.75,
						width: 60
					},
					{
						name: 'importe',
						title: 'Importe a bonificar',
						placeholder: '€420,00',
						maxlength: 10,
						x: 37,
						y: 60.55,
						width: 30
					},
					{
						name: 'mes_bonificacion',
						title: 'Mes a bonificar',
						options: __CALENDAR_MONTH,
						x: 46,
						y: 63
					},
					{
						name: 'mes_pagadero',
						title: 'Mes pagadero',
						options: __CALENDAR_MONTH,
						x: 30,
						y: 64.5
					}
				],
				4:[
					{
						name: 'empresa_ref',
						title: 'Nombre de la empresa',
						placeholder: 'Completar en página 2',
						ref: 'empresa',
						align: 'C',
						y: 23
					},
					{
						name:'tipo_formacion',
						title: 'Tipo de formación',
						options: __TIPO_FORMACION,
						placeholder: '93 866 35 23',
						x: 30,
						y: 28.5,
						width: 30
					},
					{
						name:'alumno',
						title: 'Nombre del alumno. Si son varios separados salto de línea.',
						placeholder: 'Nombre del alumno. Si son varios separados salto de línea.',
						multiline: true,
						align: 'C',
						y: 36
					},
					{
						name:'curso',
						title: 'Nombre del curso',
						placeholder: 'Nombre del curso',
						value:'PREVENCIÓN DE RIESGOS LABORALES - BÁSICO',
						align:'C',
						y: 45.85
					},
					{
						name:'componentes',
						title: 'Componentes del curso',
						placeholder: 'Componentes del curso',
						value:'CLAVES ONLINE - MANUAL - BLOC DE NOTAS - BOLÍGRAFO',
						align:'C',
						y: 54
					},
					{
						name: 'duracion',
						title: 'Duración del curso',
						placeholder: '60',
						value: '60',
						maxlength: 3,
						x: 28,
						y: 57.75,
						width: 10
					},
					{
						name: 'accion',
						title: 'Nro. acción del curso',
						placeholder: '60',
						value: '6275',
						maxlength: 3,
						x: 26.5,
						y: 61.7,
						width: 15
					},
					{
						name: 'grupo',
						title: 'Nro. de grupo',
						placeholder: '0054',
						maxlength: 8,
						x: 69,
						y: 61.7,
						width: 15
					},
					{
						name: 'ciudad',
						title: 'Ciudad',
						placeholder: 'BARCELONA',
						value: 'BARCELONA',
						maxlength: 50,
						x: 13,
						y: 69.95,
						width: 25
					},
					{
						name: 'dia',
						title: 'Día',
						options: __CALENDAR_DAY,
						x: 46,
						y: 69.95,
						width: 15
					},
					{
						name: 'mes',
						title: 'Mes',
						options: __CALENDAR_MONTH,
						x: 59,
						y: 69.95,
						width: 20
					},
					{
						name: 'anyo',
						title: 'Día',
						options: __CALENDAR_YEAR,
						x: 84,
						y: 69.95,
						width: 20
					}
				],
				5:[

					{
						name: 'empresa_ref2',
						title: 'Nombre de la empresa',
						placeholder: '',
						ref: 'empresa',
						x: 19,
						y: 66.25,
						width: 60
					},
					{
						name:'cif',
						title: 'CIF',
						placeholder: 'J66194663',
						maxlength: 10,
						x: 19,
						y: 69.25,
						width: 20
					},
					{
						name:'direccion2',
						title: 'Dirección empresa',
						placeholder: 'PL. MIL.LENARI, 4 - CORBERA DEL LLOB',
						x: 19,
						y: 72.45,
						width: 70
					},
					{
						name:'iban',
						title: 'Número de cuenta IBAN',
						placeholder: '12-3456-7890-98-0987654321',
						pattern: /(\d{2})(\d{4})?(\d{4})?(\d{2})?(\d{10})?/,
						replace: "$1-$2-$3-$4-$5",
						maxlength: 26, 
						x: 15.5,
						y: 79.15,
						width: 90,
						size: 25,
						spacing: 12.25
					},
					{
						name:'factura',
						title: 'Nro. de factura',
						placeholder: '20F00054',
						maxlength: 16,
						x: 21,
						y: 84.8,
						width: 20
					},
					{
						name:'pago',
						title: 'Tipo de pago',
						options: __TIPO_PAGO,
						x: 55,
						y: 84.8
					},
					{
						name:'pago_fecha',
						title: 'Fecha de pago',
						calendar:true,
						x: 22,
						y: 88,
						width: 20
					},
					{
						name:'fecha_pago_2',
						title: 'Fecha de pago recurrente',
						calendar:true,
						x: 45,
						y: 88,
						width: 20
					},
					{
						name:'importe2',
						title: 'Importe del cargo',
						ref: 'importe',
						x: 24,
						y: 91.3,
						width: 20
					}
				]
			},
			'ADHESION_GDF': {},
			'MATRICULA_GDF': {}
		}

		/*
		function validate(value){
		    var str = value.toString().replace(/\s/g, '');
		    return str.length === 9 && /^[679]{1}[0-9]{8}$/.test(str);
		}
		*/
	function showOptions(){
		for(var id in __INPUTS){
			$('#documents').append($('<option>', {
			    value: id,
			    text: id
			}))
		}
	document.querySelector('.spinner-container').remove()
	document.querySelector('.section').classList.remove('is-hidden')

	}

	function showInputs(page_no){
		var doc = __INPUTS[__CURRENT_DOC]
		var fields = doc[page_no]

		if($('.page').length === 0 && $(fields).length){
			snackbar('default',"Utiliza las flechas ⬅️➡️ para navegar a través de las páginas",3000)
		}

		if($('.page' + page_no).length){
			$(fields).each((i, item) => {
				if(item.ref){
					$('#'+item.name).val($('#'+item.ref).val())
				}
			})
			$('.page' + page_no).show()
		} else {
			$("<div>")
			     .attr("class", "page page"+page_no)
			     .appendTo("#pdf-contents");

			$(fields).each((i, item) => {
				var style = ''
				var disabled = false
				var maxlength = item.maxlength||999
				var value = item.value||''
				var placeholder = item.placeholder||''

				if(item.align){
					style+= 'text-align:center;'
					if(item.align==='C'){
						style+='margin: 0 auto;display: inherit;'
					}
				}

				if(item.x){
					style+=`left:${item.x}%;`
				}

				if(item.y){
					const y = item.y - 2.5
					style+=`top:${y}%;`
				}
				if(item.width){
					style+=`width:${item.width}%;min-width:${item.width}%;`
				}
				if(item.ref){
					value = $('#'+item.ref).val()
					var pageno = 0
					for(var i in doc){
						for(var j in doc[i]){
							if(doc[i][j].name === item.ref){
								pageno = i
							}
						}
					}
					placeholder = `{${item.ref} p${pageno}}`
					disabled = true
				}

				if(item.calendar){

					$("<input>")
					    .attr("class", "input")
					    .attr("id", item.name)
						.attr("name", item.name)
					    .attr("title", item.title)
					    .attr("placeholder", placeholder)
					    .attr("value", value)
					    .attr("style", style)
					    .attr("data-toggle","datepicker")
					  	.attr("disabled", disabled)
					    .appendTo(".page"+page_no);

				} else if(item.multiline){
					$("<textarea>")
						.attr("class", "textarea")
						.attr("id", item.name)
						.attr("name", item.name)
					    .attr("title", item.title)
					    .attr("placeholder", placeholder)
					    .attr("style", style)
					    .attr("disabled", disabled)
					    .attr("rows",2)
					    .val(value)
					    .appendTo(".page"+page_no);

				} else if(item.options){

					var options = ''
					$(item.options).each((i,option) => {
						options+=`<option value="${option}">${option}</option>`;
					})

					var select = $("<select>")
						.attr("id", item.name)
						.attr("name", item.name)
					    .attr("title", item.title)
					    .attr("disabled", disabled)
					    .html(options)

					$("<div class='select'>")
						.attr("style", style)
						.append(select)
					    .appendTo(".page"+page_no);

				} else {
					$("<input>")
					    .attr("class", "input")
					    .attr("id", item.name)
						.attr("name", item.name)
					    .attr("title", item.title)
					    .attr("maxlength", maxlength)
					    .attr("placeholder", placeholder)
					    .attr("value", value)
					    .attr("style", style)
					  	.attr("disabled", disabled)
					    .appendTo(".page"+page_no);
				}

				if(item.replace){
					$('#'+item.name).on('keyup',(e) => {
						var n = $(e.target).val()
						n = n.split('-').join('')
						n = n.replace(item.pattern, item.replace)
						n = n.split('--').join('')
						$(e.target).val(n)
					})
				}

				$('[data-toggle="datepicker"]').datepicker({
			  		format: 'dd/mm/yyyy'
				})
			})
		}

		$('input:visible').first().focus()
	}

	function setInputValues(){
		var doc = __INPUTS[__CURRENT_DOC]
		var data = {}
		for(var pageno in doc){
			for(var fielno in doc[pageno]){
				doc[pageno][fielno].value = $('#'+doc[pageno][fielno].name).val()
			}
		}
	}

	function showPDF(pdf_name) {
		$("#pdf-loader").show();

		if(__CURRENT_DOC!= pdf_name){
			$('.page').remove()
		}

		__CURRENT_DOC = pdf_name
		PDFJS.getDocument({ url: __DOCS_PATH + pdf_name + '.pdf' }).then(function(pdf_doc) {
			__PDF_DOC = pdf_doc;
			__TOTAL_PAGES = __PDF_DOC.numPages;
			
			// Hide the pdf loader and show pdf container in HTML
			$("#pdf-loader").hide();
			$("#pdf-contents").show();
			$("#pdf-actions").show();
			$("#pdf-total-pages").text(__TOTAL_PAGES);

			// Show the first page
			showPage(1);

		}).catch(function(error) {
			// If error re-show the upload button
			$("#pdf-loader").hide();
			
			snackbar('error',error.message)
		});;
	}

	function showPage(page_no) {
		__PAGE_RENDERING_IN_PROGRESS = 1;
		__CURRENT_PAGE = page_no;


		// Disable Prev & Next buttons while page is being loaded
		$("#pdf-next, #pdf-prev").attr('disabled', 'disabled');

		// While page is being rendered hide the canvas and show a loading message
		$("#pdf-canvas").hide();
		$("#page-loader").show();
		$('.page').hide()

		// Update current page in HTML
		$("#pdf-current-page").text(page_no);
		
		// Fetch the page
		__PDF_DOC.getPage(page_no).then(function(page) {
			// As the canvas is of a fixed width we need to set the scale of the viewport accordingly
			var scale_required = __CANVAS.width / page.getViewport(1).width;

			// Get viewport of the page at required scale
			var viewport = page.getViewport(scale_required);

			// Set canvas height
			__CANVAS.height = viewport.height;

			var renderContext = {
				canvasContext: __CANVAS_CTX,
				viewport: viewport
			};
			
			// Render the page contents in the canvas
			page.render(renderContext).then(function() {
				__PAGE_RENDERING_IN_PROGRESS = 0;

				// Re-enable Prev & Next buttons
				$("#pdf-next, #pdf-prev").removeAttr('disabled');

				// Show the canvas and hide the page loader
				$("#pdf-canvas").show();
				$("#page-loader").hide()
				showInputs(page_no)
			});
		});
	}

	$('#documents').change((e) => {
		var pdf_name = $(e.target).val()
		if(pdf_name.length){
			showPDF(pdf_name)
		}
	})

	// Previous page of the PDF
	$("#pdf-prev").on('click', function() {
		if(__CURRENT_PAGE != 1)
			showPage(--__CURRENT_PAGE);
	});

	$("#pdf-next").on('click', function() {
		if(__CURRENT_PAGE != __TOTAL_PAGES)
			showPage(++__CURRENT_PAGE);
	});

	$("#pdf-send").on('click', function(e) {
		var t = $(this)
		setInputValues()

		swal({
		  title: "Compartir documento",
		  text: "Ingresa el email con el que deseas compartir el documento:",
		  type: "input",
		  showCancelButton: true,
		  closeOnConfirm: false,
		  inputPlaceholder: "joseperez@gmail.com"
		},
		function(email){
			if (email === false) return false;

			if (email === "") {
				swal.showInputError("por favor ingrese un email");
				return false
			}
			swal.close()
			t.addClass('is-loading')
			snackbar('default','Se inició el envío del documento, por favor espere...',2000)
			$.ajax({
				type:'post',
				url: '/v1.0/send',
				data:{
					pdf_name: __CURRENT_DOC,
					values: __INPUTS[__CURRENT_DOC],
					email: email
				},
				success: function(res) {
					t.removeClass('is-loading')
					if(res.status === 'success'){
						snackbar('success',`Se completó el envío de documento PDF a ${email}.`,3000)
					} else {
						snackbar('error','Hubo un error al enviar email. Por favor intente nuevamente en unos instantes.',3000)    		
					}
				}
			})		  
		});
	})

	// Download PDF
	$("#pdf-download").on('click', function(e) {
		var t = $(this)
		t.addClass('is-loading')
		setInputValues()
		snackbar('default','Se inició la impresión del documento, por favor espere...',2000)
      	$.ajax({
	        type:'post',
	        url: '/v1.0/download',
	        dataType: 'binary',
	        data:{
	        	pdf_name: __CURRENT_DOC,
	        	values: __INPUTS[__CURRENT_DOC]
	        },
	        xhrFields: {
	          responseType: 'blob'
	        },            
	        success: function(res) {
	          t.removeClass('is-loading')
	          var blob = new Blob([res], { type: 'application/pdf' });
	          var link = document.createElement('a')
	          link.href = window.URL.createObjectURL(blob)
	          link.download = __CURRENT_DOC + '.pdf'
	          document.body.appendChild(link);
	          link.click()
	          snackbar('success','Se completó la descarga de documento PDF.',5000)
	        },
	        error: function(xhr) {
	          $(t).removeClass('is-loading')
	          swal('Error al generar documento',"Por favor intente nuevamente en unos instantes.")
	        }
	    })
	})

	$(document).keydown(function(e) {
		if(e.keyCode=='37'){
			$('#pdf-prev').click()
		}
		if(e.keyCode=='38'){
			if($('input:focus').length){
				$('input:focus').prev().focus()
			} else {
				$('input:visible').last().focus()
			}
		}
		if(e.keyCode=='39'){
			$('#pdf-next').click()	
		}
		if(e.keyCode=='40'){
			if($('input:focus').length){
				$('input:focus').next().focus()
			} else {
				$('input:visible').first().focus()
			}
		}
	})

	showOptions()
})

