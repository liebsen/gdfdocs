$(function(){

    var snackbar = function(messageType,message,timeout){
      if(timeout===undefined) timeout = 5000

      const removes = [
        'ui-snackbar--is-inactive',
        'ui-snackbar--success',
        'ui-snackbar--error',
        'ui-snackbar--default'
      ]

      const adds = [
        'ui-snackbar--is-active',
        'ui-snackbar--' + messageType,
      ]

      removes.forEach(remove => {
        document.querySelector('.ui-snackbar').classList.remove(remove)
      })

      adds.forEach(add => {
        document.querySelector('.ui-snackbar').classList.add(add)
      })

      document.querySelector('.ui-snackbar__message').innerHTML = message
      
      setTimeout(() => {
        $('.ui-snackbar').removeClass('ui-snackbar--is-active').addClass('ui-snackbar--is-inactive')
      },timeout)
    }

	/*
1. CCF - Pack Documentación (4 páginas)
2. Hoja de Adhesión (2 páginas)
3. Matrícula GDF (1 página)
4. Orden Domiliación (1 página)
*/

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
						align: 'center',
						y: 23.5,
						pdfi : {
							align: 'C',
							x: 0,
							y: 85,
							w: 280,
							h: 40
						}
					},
					{
						name: 'telefono',
						title: 'Nro. de teléfono',
						placeholder: '93 866 35 23',
						value: '93 866 35 23',
						x: 58,
						y: 49,
						width: 30,
						pdfi : {
							x: 163,
							y: 213
						}
					},
					{
						name: 'email',
						title: 'Email de contacto',
						placeholder: 'tutor@gdf-formacion.com',
						value: 'tutor@gdf-formacion.com',
						x: 47,
						y: 53.5,
						width: 40,
						pdfi : {
							x: 133,
							y: 226
						}
					},
					{
						name: 'horario',
						title: 'Horario de tutorías',
						placeholder: 'de 9 a 14 de Lunes a Viernes',
						value: 'de 9 a 14 de Lunes a Viernes',
						x: 50,
						y: 58,
						width: 45,
						pdfi : {
							x: 142,
							y: 239
						}
					},
					{
						name: 'direccion',
						title: 'Dirección',
						placeholder: 'Ctra. de Granollers a Carcadeu K.1.5 of. 4 - 08520 Les Franqueses del Vallès',
						value: 'Ctra. de Granollers a Carcadeu K.1.5 of. 4 - 08520 Les Franqueses del Vallès',
						align: 'center',
						y: 67.5,
						pdfi : {
							align:'C',
							x: 30,
							y: 284
						}
					}
				],
				2:[
					{
						name: 'empresa',
						title: 'Nombre de la empresa',
						placeholder: 'Empresa',
						x: 27,
						y: 55,
						width: 60,
						pdfi : {
							x: 88,
							y: 237
						}
					},
					{
						name: 'importe',
						title: 'Importe a bonificar',
						placeholder: '€420,00',
						x: 35,
						y: 59.5,
						width: 20,
						pdfi : {
							x: 105,
							y: 248
						}
					},
					{
						name: 'mes_bonificacion',
						title: 'Mes a bonificar',
						//regexp: '/^[679]{1}[0-9]{8}$/',
						/*/^\+(?:[0-9] ?){6,14}[0-9]$/*/
						options: __CALENDAR_MONTH,
						x: 43,
						y: 64,
						pdfi : {
							x: 140,
							y: 258
						}
					},
					{
						name: 'mes_pagadero',
						title: 'Mes pagadero',
						options: __CALENDAR_MONTH,
						x: 70,
						y: 64,
						pdfi : {
							x: 75,
							y: 264
						}
					}
				],
				4:[
					{
						name: 'empresa_ref',
						title: 'Nombre de la empresa',
						placeholder: 'Completar en página 2',
						ref: 'empresa',
						align: 'center',
						y: 21.5,
						pdfi : {
							align: 'C',
							x: 0,
							y: 75.5,
							w: 280,
							h: 40
						}
					},
					{
						name:'tipo_formacion',
						title: 'Tipo de formación',
						options: __TIPO_FORMACION,
						placeholder: '93 866 35 23',
						x: 30,
						y: 26,
						width: 30,
						pdfi : {
							x: 93,
							y: 116
						}
					},
					{
						name:'alumno',
						title: 'Nombre del alumno. Si son varios separados por coma.',
						placeholder: 'Nombre del alumno. Si son varios separados por coma.',
						multiline: true,
						x: 24,
						y: 30.5,
						width: 70,
						pdfi : {
							align: 'C',
							x: 0,
							y: 110,
							w: 280,
							h: 80
						}
					},
					{
						name:'curso',
						title: 'Nombre del curso',
						placeholder: 'Nombre del curso',
						value:'PREVENCIÓN DE RIESGOS LABORALES - BÁSICO',
						align:'center',
						y: 43.5,
						pdfi : {
							align: 'C',
							x: 0,
							y: 144,
							w: 280,
							h: 80
						}
					},
					{
						name:'componentes',
						title: 'Componentes del curso',
						placeholder: 'Componentes del curso',
						value:'CLAVES ONLINE - MANUAL - BLOC DE NOTAS - BOLÍGRAFO',
						align:'center',
						y: 51.5,
						pdfi : {
							align: 'C',
							x: 0,
							y: 178,
							w: 280,
							h: 80
						}
					},
					{
						name: 'duracion',
						title: 'Duración del curso',
						placeholder: '60',
						value: '60',
						x: 26,
						y: 56,
						width: 10,
						pdfi : {
							x: 85,
							y: 237
						}
					},
					{
						name: 'accion',
						title: 'Nro. acción del curso',
						placeholder: '60',
						value: '6275',
						x: 26,
						y: 60.5,
						width: 12,
						pdfi : {
							x: 80,
							y: 252.5
						}
					},
					{
						name: 'grupo',
						title: 'Nro. de grupo',
						placeholder: '0054',
						x: 69,
						y: 60.5,
						width: 12,
						pdfi : {
							x: 190,
							y: 252.5
						}
					},
					{
						name: 'ciudad',
						title: 'Ciudad',
						placeholder: 'BARCELONA',
						value: 'BARCELONA',
						x: 12,
						y: 69,
						width: 25,
						pdfi : {
							x: 38,
							y: 286.5
						}
					},
					{
						name: 'dia',
						title: 'Día',
						options: __CALENDAR_DAY,
						x: 45,
						y: 69,
						width: 13,
						pdfi : {
							x: 135,
							y: 286.5
						}
					},
					{
						name: 'dia',
						title: 'Día',
						options: __CALENDAR_MONTH,
						x: 59,
						y: 69,
						width: 20,
						pdfi : {
							x: 170,
							y: 286.5
						}
					},
					{
						name: 'dia',
						title: 'Día',
						options: __CALENDAR_YEAR,
						x: 85,
						y: 69,
						width: 17,
						pdfi : {
							x: 250,
							y: 286.5
						}
					}
				],
				5:[

					{
						name: 'empresa_ref2',
						title: 'Nombre de la empresa',
						placeholder: '',
						ref: 'empresa',
						x: 19,
						y: 63.5,
						width: 60,
						pdfi : {
							x: 48,
							y: 272
						}
					},
					{
						name:'cif',
						title: 'CIF',
						placeholder: 'J66194663',
						x: 19,
						y: 68,
						width: 20,
						pdfi : {
							x: 36,
							y: 284
						}
					},
					{
						name:'direccion2',
						title: 'Dirección empresa',
						placeholder: 'PL. MIL.LENARI, 4 - CORBERA DEL LLOB',
						x: 19,
						y: 72.5,
						width: 70,
						pdfi : {
							x: 52,
							y: 297
						}
					},
					{
						name:'iban',
						title: 'Número de cuenta IBAN',
						placeholder: '12-3456-7890-98-0987654321',
						pattern: /(\d{2})(\d{4})?(\d{4})?(\d{2})?(\d{10})?/,
						replace: "$1-$2-$3-$4-$5",
						x: 12,
						y: 77,
						width: 90,
						pdfi : {
							x: 42,
							y: 325,
							size: 25,
						}
					},
					{
						name:'factura',
						title: 'Nro. de factura',
						placeholder: '20F00054',
						x: 19,
						y: 82.5,
						width: 20,
						pdfi : {
							x: 59,
							y: 348
						}
					},
					{
						name:'pago',
						title: 'Tipo de pago',
						options: __TIPO_PAGO,
						x: 55,
						y: 82.5,
						pdfi : {
							x: 160,
							y: 348
						}
					},
					{
						name:'pago_fecha',
						title: 'Fecha de pago',
						calendar:true,
						x: 19,
						y: 87,
						width: 20,
						pdfi : {
							x: 65,
							y: 360
						}
					},
					{
						name:'fecha_pago_2',
						title: 'Fecha de pago recurrente',
						calendar:true,
						x: 45,
						y: 87,
						width: 20,
						pdfi : {
							x: 120,
							y: 360
						}
					},
					{
						name:'importe2',
						title: 'Importe del cargo',
						ref: 'importe',
						x: 19,
						y: 91.5,
						width: 20,
						pdfi : {
							x: 83,
							y: 374.5
						}
					}
				]
			},
			'ADHESION': {},
			'MATRICULA': {}
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
	}

	function showInputs(page_no){
		var doc = __INPUTS[__CURRENT_DOC]
		var fields = doc[page_no]

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
			     //.attr("style", 'width:' + $('#pdf-canvas').width() + 'px;' + 'height:' + $('#pdf-canvas').height() + 'px;')
			     .appendTo("#pdf-contents");

			$(fields).each((i, item) => {
				var style = ''
				var disabled = false
				var value = item.value||''
				var placeholder = item.placeholder||''
				if(item.align){
					style+= 'text-align:center;'
					if(item.align==='center'){
						style+='margin: 0 auto;display: inherit;'
					}
				}
				if(item.x)
					style+=`left:${item.x}%;`
				if(item.y)
					style+=`top:${item.y}%;`
				if(item.width)
					style+=`width:${item.width}%;min-width:${item.width}%;`
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
					placeholder = `Referencia:${item.ref} en página ${pageno}`
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
	}

	function getValues(){
		var doc = __INPUTS[__CURRENT_DOC]
		var data = {}
		for(var pageno in doc){
			for(var fielno in doc[pageno]){
				if(!data[pageno]) data[pageno] = []
				data[pageno].push({
					pdfi: doc[pageno][fielno].pdfi,
					value : $('#'+doc[pageno][fielno].name).val()
				})
			}
		}

		return data
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
			
			alert(error.message);
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

	// Download PDF
	$("#pdf-download").on('click', function(e) {
		var t = $(this)
		t.addClass('is-loading')

      	$.ajax({
	        type:'post',
	        url: '/v1.0/download',
	        dataType: 'binary',
	        data:{
	        	pdf_name: __CURRENT_DOC,
	        	values: getValues()
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
	          snackbar('success','Se completó la descarga de documento PDF.',3000)
	        },
	        error: function(xhr) {
	          $(t).removeClass('is-loading')
	          swal('Error al generar documento',"Por favor intente nuevamente en unos instantes.")
	        }
	    })
	})

	showOptions()
})