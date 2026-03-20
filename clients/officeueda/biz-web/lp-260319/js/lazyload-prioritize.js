document.addEventListener("DOMContentLoaded", () => {
  let lazyImgs = document.querySelectorAll("img[loading='lazy']")
  let eagerImgs = document.querySelectorAll("img:not([loading='lazy'])")
  let loaded = 0

  function prioritizeLazy() {
    if (lazyImgs.length === 0) return
    let sorted = []
    lazyImgs.forEach(img => {
      let dist = Math.abs(img.getBoundingClientRect().top)
      sorted.push({ img, dist })
    })
    sorted.sort((a, b) => a.dist - b.dist)
    sorted.forEach(({ img }, i) => {
      setTimeout(() => {
        img.removeAttribute("loading")
        img.src = img.src
      }, 100 * i)
    })
  }

  function onEagerLoad() {
    loaded++
    if (loaded === eagerImgs.length) prioritizeLazy()
  }

  if (eagerImgs.length === 0) {
    prioritizeLazy()
    return
  }

  eagerImgs.forEach(img => {
    if (img.complete) {
      onEagerLoad()
    } else {
      img.onload = onEagerLoad
      img.onerror = onEagerLoad
    }
  })
})
