
!function ($) {
    "use strict";

    var SweetAlert = function () { };

    //examples 
    SweetAlert.prototype.init = function () {

        //Basic
        $('#sa-basic').click(function () {
            Swal.fire("Here's a message!");
        });

        //A title with a text under
        $('#sa-title').click(function () {
            Swal.fire("Here's a message!", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed lorem erat eleifend ex semper, lobortis purus sed.")
        });

        //Success Message
        $('#sa-success').click(function () {
            Swal.fire("Good job!", "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed lorem erat eleifend ex semper, lobortis purus sed.", "success")
        });

        //Warning Message
        $('#sa-warning').click(function () {
            Swal.fire({
                title: "Are you sure?",
                text: "You will not be able to recover this imaginary file!",
                type: "warning",
                showCancelButton: true,
                confirmButtonColor: "#DD6B55",
                confirmButtonText: "Yes, delete it!",
                closeOnConfirm: false
            }, function () {
                swal("Deleted!", "Your imaginary file has been deleted.", "success");
            });
        });

        //Custom Image
        $('#sa-image').click(function () {
            Swal.fire({
                title: "Govinda!",
                text: "Recently joined twitter",
                imageUrl: "../assets/images/users/2.jpg"
            });
        });

        //Auto Close Timer
        $('#sa-close').click(function () {
            Swal.fire({
                title: "Auto close alert!",
                text: "I will close in 2 seconds.",
                timer: 2000,
                showConfirmButton: false
            });
        });

        $("#model-error-icon").click(function () {
            Swal.fire({
                type: 'error',
                title: 'Oops...',
                text: 'Something went wrong!',
                footer: '<a href>Why do I have this issue?</a>'
            })
        });

        $("#sa-html").click(function () {
            Swal.fire({
                title: '<strong>HTML <u>example</u></strong>',
                type: 'info',
                html:
                    'You can use <b>bold text</b>, ' +
                    '<a href="//github.com">links</a> ' +
                    'and other HTML tags',
                showCloseButton: true,
                showCancelButton: true,
                focusConfirm: false,
                confirmButtonText:
                    '<i class="fa fa-thumbs-up"></i> Great!',
                confirmButtonAriaLabel: 'Thumbs up, great!',
                cancelButtonText:
                    '<i class="fa fa-thumbs-down"></i>',
                cancelButtonAriaLabel: 'Thumbs down',
            })
        });

        $("#sa-position").click(function () {
            Swal.fire({
                position: 'top-end',
                type: 'success',
                title: 'Your work has been saved',
                showConfirmButton: false,
                timer: 1500
            })
        });

        $("#sa-animation").click(function () {
            Swal.fire({
                title: 'Custom animation with Animate.css',
                animation: false,
                customClass: {
                    popup: 'animated tada'
                }
            })
        });

        $("#sa-confirm").click(function () {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                type: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.value) {
                    Swal.fire(
                        'Deleted!',
                        'Your file has been deleted.',
                        'success'
                    )
                }
            })
        });

        $("#sa-passparameter").click(function () {
            const swalWithBootstrapButtons = Swal.mixin({
                customClass: {
                    confirmButton: 'btn btn-success',
                    cancelButton: 'mr-2 btn btn-danger'
                },
                buttonsStyling: false,
            })

            swalWithBootstrapButtons.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                type: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel!',
                reverseButtons: true
            }).then((result) => {
                if (result.value) {
                    swalWithBootstrapButtons.fire(
                        'Deleted!',
                        'Your file has been deleted.',
                        'success'
                    )
                } else if (
                    // Read more about handling dismissals
                    result.dismiss === Swal.DismissReason.cancel
                ) {
                    swalWithBootstrapButtons.fire(
                        'Cancelled',
                        'Your imaginary file is safe :)',
                        'error'
                    )
                }
            })
        });

        $("#sa-bg").click(function () {
            Swal.fire({
                title: 'Custom width, padding, background.',
                width: 600,
                padding: '3em',
                background: '#fff url(../assets/images/background/active-bg.png)',
                backdrop: `
                        rgba(0,0,123,0.4)
                        url("../assets/images/background/nyan-cat.gif")
                        center left
                        no-repeat
                    `
            })
        });

        $("#sa-autoclose").click(function () {
            let timerInterval
            Swal.fire({
                title: 'Auto close alert!',
                html: 'I will close in <strong></strong> seconds.',
                timer: 2000,
                onBeforeOpen: () => {
                    Swal.showLoading()
                    timerInterval = setInterval(() => {
                        Swal.getContent().querySelector('strong')
                            .textContent = Swal.getTimerLeft()
                    }, 100)
                },
                onClose: () => {
                    clearInterval(timerInterval)
                }
            }).then((result) => {
                if (
                    // Read more about handling dismissals
                    result.dismiss === Swal.DismissReason.timer
                ) {
                    console.log('I was closed by the timer')
                }
            })
        });

        $("#sa-rtl").click(function () {
            Swal.fire({
                title: 'هل تريد الاستمرار؟',
                type: 'question',
                customClass: {
                    icon: 'swal2-arabic-question-mark'
                },
                confirmButtonText: 'نعم',
                cancelButtonText: 'لا',
                showCancelButton: true,
                showCloseButton: true
            })
        });

        $("#sa-ajax").click(function () {
            Swal.fire({
                title: 'Submit your Github username',
                input: 'text',
                inputAttributes: {
                    autocapitalize: 'off'
                },
                showCancelButton: true,
                confirmButtonText: 'Look up',
                showLoaderOnConfirm: true,
                preConfirm: (login) => {
                    return fetch(`//api.github.com/users/${login}`)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(response.statusText)
                            }
                            return response.json()
                        })
                        .catch(error => {
                            Swal.showValidationMessage(
                                `Request failed: ${error}`
                            )
                        })
                },
                allowOutsideClick: () => !Swal.isLoading()
            }).then((result) => {
                if (result.value) {
                    Swal.fire({
                        title: `${result.value.login}'s avatar`,
                        imageUrl: result.value.avatar_url
                    })
                }
            })
        });

        $("#sa-chain").click(function () {
            Swal.mixin({
                input: 'text',
                confirmButtonText: 'Next &rarr;',
                showCancelButton: true,
                progressSteps: ['1', '2', '3']
            }).queue([
                {
                    title: 'Question 1',
                    text: 'Chaining swal2 modals is easy'
                },
                'Question 2',
                'Question 3'
            ]).then((result) => {
                if (result.value) {
                    Swal.fire({
                        title: 'All done!',
                        html:
                            'Your answers: <pre><code>' +
                            JSON.stringify(result.value) +
                            '</code></pre>',
                        confirmButtonText: 'Lovely!'
                    })
                }
            })
        });

        $("#sa-queue").click(function () {
            const ipAPI = 'https://api.ipify.org?format=json'

            Swal.queue([{
                title: 'Your public IP',
                confirmButtonText: 'Show my public IP',
                text:
                    'Your public IP will be received ' +
                    'via AJAX request',
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    return fetch(ipAPI)
                        .then(response => response.json())
                        .then(data => Swal.insertQueueStep(data.ip))
                        .catch(() => {
                            Swal.insertQueueStep({
                                type: 'error',
                                title: 'Unable to get your public IP'
                            })
                        })
                }
            }])
        });

        $("#sa-timerfun").click(function () {
            let timerInterval
            Swal.fire({
                title: 'Auto close alert!',
                html:
                    'I will close in <strong></strong> seconds.<br/><br/>' +
                    '<button id="increase" class="btn btn-warning">' +
                    'I need 5 more seconds!' +
                    '</button><br/>' +
                    '<button id="stop" class="btn btn-danger mt-1">' +
                    'Please stop the timer!!' +
                    '</button><br/>' +
                    '<button id="resume" class="btn btn-success mt-1" disabled>' +
                    'Phew... you can restart now!' +
                    '</button><br/>' +
                    '<button id="toggle" class="btn btn-primary mt-1">' +
                    'Toggle' +
                    '</button>',
                timer: 10000,
                onBeforeOpen: () => {
                    const content = Swal.getContent()
                    const $ = content.querySelector.bind(content)

                    const stop = $('#stop')
                    const resume = $('#resume')
                    const toggle = $('#toggle')
                    const increase = $('#increase')

                    Swal.showLoading()

                    function toggleButtons() {
                        stop.disabled = !Swal.isTimerRunning()
                        resume.disabled = Swal.isTimerRunning()
                    }

                    stop.addEventListener('click', () => {
                        Swal.stopTimer()
                        toggleButtons()
                    })

                    resume.addEventListener('click', () => {
                        Swal.resumeTimer()
                        toggleButtons()
                    })

                    toggle.addEventListener('click', () => {
                        Swal.toggleTimer()
                        toggleButtons()
                    })

                    increase.addEventListener('click', () => {
                        Swal.increaseTimer(5000)
                    })

                    timerInterval = setInterval(() => {
                        Swal.getContent().querySelector('strong')
                            .textContent = (Swal.getTimerLeft() / 1000)
                                .toFixed(0)
                    }, 100)
                },
                onClose: () => {
                    clearInterval(timerInterval)
                }
            })
        });
    },
        //init
        $.SweetAlert = new SweetAlert, $.SweetAlert.Constructor = SweetAlert
}(window.jQuery),

    //initializing 
    function ($) {
        "use strict";
        $.SweetAlert.init()
    }(window.jQuery);