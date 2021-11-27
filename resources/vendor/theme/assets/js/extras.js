document.addEventListener("DOMContentLoaded", () => {
  // Sortable
  let element = null;

  element = document.getElementById("sortable-style-1");
  if (element) {
    const sortable = Sortable.create(element, {
      animation: 150,
    });
  }

  element = document.getElementById("sortable-style-2");
  if (element) {
    const sortable = Sortable.create(element, {
      handle: ".handle",
      animation: 150,
    });
  }

  element = document.getElementById("sortable-style-3");
  if (element) {
    const sortable = Sortable.create(element, {
      animation: 150,
    });
  }

  // Editors
  // CKEditor
  const editor = document.getElementById("ckeditor");
  if (editor) {
    ClassicEditor.create(editor);
  }

  // Carousel
  const carousel = document.getElementById("carousel-style-1");
  if (carousel) {
    const dir = () => {
      if (document.dir == "rtl") {
        return "rtl";
      } else {
        return "ltr";
      }
    };

    new Glide(carousel, {
      direction: dir(),
      type: "carousel",
      perView: 4,
      gap: 20,
      breakpoints: {
        640: {
          perView: 1,
        },
        768: {
          perView: 2,
        },
      },
    }).mount();
  }
});
