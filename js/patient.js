
    const editPatientModal = document.getElementById('editPatientModal');
    editPatientModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;

        document.getElementById('edit_patient_id').value = button.getAttribute('data-id');
        document.getElementById('edit_case_id').value = button.getAttribute('data-case');
        document.getElementById('edit_first_name').value = button.getAttribute('data-first');
        document.getElementById('edit_last_name').value = button.getAttribute('data-last');
        document.getElementById('edit_gender').value = button.getAttribute('data-gender');
        document.getElementById('edit_date_of_birth').value = button.getAttribute('data-dob');
        document.getElementById('edit_contact_number').value = button.getAttribute('data-contact');
        document.getElementById('edit_address').value = button.getAttribute('data-address');
        document.getElementById('edit_medical_history').value = button.getAttribute('data-history');
    });

