
// let counter = 0
let $loader = null

function addClass (dom, clsName) {
  if (dom.classList) {
    dom.classList.add(clsName)
  } else {
    if (!dom.className.includes(clsName)) {
      dom.className += ' ' + clsName
    }
  }
}

function removeClass (dom, clsName) {
  if (dom.classList) {
    dom.classList.remove(clsName)
  } else {
    if (dom.className.includes(clsName)) {
      dom.className = dom.className.split(' ').filter(name => name !== clsName).join(' ')
    }
  }
}

export default class PaceLoader {
  static install () {
    $loader = document.createElement('div')
    $loader.setAttribute('class', 'pace-loader')

    let child = document.createElement('div')
    child.setAttribute('class', 'pace-loader-activity')
    $loader.appendChild(child)
    child = document.createElement('div')
    child.setAttribute('class', 'pace-loader-progress')
    $loader.appendChild(child)
    document.body.appendChild($loader)
  }

  static start () {
    // if ((counter += 1) <= 1) {
    //   addClass($loader, 'active')
    // }
    addClass($loader, 'active')
  }
  static finish () {
    // if ((counter -= 1) <= 0) {
    //   removeClass($loader, 'active')
    // }
    removeClass($loader, 'active')
  }
}
