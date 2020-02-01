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

