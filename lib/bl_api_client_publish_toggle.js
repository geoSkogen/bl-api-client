console.log('running publish toggle script')
var dir = ['false','true'];
var index = [1,0]
var box = document.querySelector('#publish_now')
var index_els =  [box]

var app = {
  'publish_now' : function (el) {
    var test = dir.indexOf(el.value)
    el.value = dir[index[test]]
    console.log('is')
    console.log(el.value)
  }
}

index_els.forEach( function (el) {
  el.addEventListener('change', function () {
    app[el.id](this)
  })
})
