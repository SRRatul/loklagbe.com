document.addEventListener("DOMContentLoaded", () => {

  const mobileMenuBtn = document.querySelector(".mobile-menu-btn")
  const navMenu = document.querySelector(".nav-menu")

  if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener("click", () => {
      navMenu.style.display = navMenu.style.display === "block" ? "none" : "block"
    })
  }


  const facebookBtn = document.getElementById("facebook-btn")
  const googleBtn = document.getElementById("google-btn")

  if (facebookBtn) {
    facebookBtn.addEventListener("click", () => {
      alert("Redirecting to Facebook login...")

    })
  }

  if (googleBtn) {
    googleBtn.addEventListener("click", () => {
      alert("Redirecting to Google login...")

    })
  }


  const forms = document.querySelectorAll("form")

  forms.forEach((form) => {
    form.addEventListener("submit", (event) => {
      const inputs = form.querySelectorAll("input[required], textarea[required]")
      let isValid = true

      inputs.forEach((input) => {
        if (!input.value.trim()) {
          isValid = false
          input.style.borderColor = "#d21f50"
        } else {
          input.style.borderColor = "#ccc"
        }

        if (input.type === "email" && input.value.trim()) {
          const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
          if (!emailPattern.test(input.value)) {
            isValid = false
            input.style.borderColor = "#d21f50"
          }
        }
      })

      if (!isValid) {
        event.preventDefault()
        alert("Please fill in all required fields correctly.")
      }
    })
  })


  const testimonialSlider = document.querySelector(".testimonials-slider")

  if (testimonialSlider) {
    let isDown = false
    let startX
    let scrollLeft

    testimonialSlider.addEventListener("mousedown", (e) => {
      isDown = true
      startX = e.pageX - testimonialSlider.offsetLeft
      scrollLeft = testimonialSlider.scrollLeft
    })

    testimonialSlider.addEventListener("mouseleave", () => {
      isDown = false
    })

    testimonialSlider.addEventListener("mouseup", () => {
      isDown = false
    })

    testimonialSlider.addEventListener("mousemove", (e) => {
      if (!isDown) return
      e.preventDefault()
      const x = e.pageX - testimonialSlider.offsetLeft
      const walk = (x - startX) * 2
      testimonialSlider.scrollLeft = scrollLeft - walk
    })
  }
})

