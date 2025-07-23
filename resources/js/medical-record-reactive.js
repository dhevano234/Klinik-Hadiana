// File: resources/js/medical-record-reactive.js
// JavaScript untuk reactive behavior medical record form

document.addEventListener('DOMContentLoaded', function() {
    console.log('🏥 Medical Record Reactive System Loading...');
    
    // Function untuk update nomor rekam medis
    function updateMedicalRecordNumber(userId) {
        if (!userId) {
            setMRNField('');
            return;
        }
        
        // Fetch user data via AJAX
        fetch(`/api/users/${userId}/medical-record`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.medical_record_number) {
                    setMRNField(data.medical_record_number);
                } else {
                    setMRNField('Belum ada');
                }
            })
            .catch(error => {
                console.error('Error fetching medical record:', error);
                setMRNField('Error loading');
            });
    }
    
    // Function untuk set nilai field MRN
    function setMRNField(value) {
        const mrnField = document.querySelector('input[wire\\:model="medical_record_number"]');
        if (mrnField) {
            mrnField.value = value;
            mrnField.dispatchEvent(new Event('input'));
        }
    }
    
    // Listen untuk perubahan user selection
    document.addEventListener('change', function(e) {
        if (e.target.matches('select[wire\\:model="user_id"]')) {
            const userId = e.target.value;
            console.log('👤 User selected:', userId);
            updateMedicalRecordNumber(userId);
        }
    });
    
    // Livewire hooks
    if (typeof Livewire !== 'undefined') {
        Livewire.hook('morph.updated', () => {
            console.log('🔄 Form updated, checking for user selection...');
            
            const userSelect = document.querySelector('select[wire\\:model="user_id"]');
            if (userSelect && userSelect.value) {
                updateMedicalRecordNumber(userSelect.value);
            }
        });
        
        Livewire.on('user-selected', (data) => {
            console.log('🎯 Livewire user-selected event:', data);
            updateMedicalRecordNumber(data.userId);
        });
    }
    
    console.log('✅ Medical Record Reactive System Ready');
});

// Alternative: Pure Livewire Alpine.js approach
function medicalRecordReactive() {
    return {
        selectedUserId: null,
        medicalRecordNumber: '',
        patientDetails: {},
        
        init() {
            console.log('🏥 Alpine Medical Record Component Initialized');
            this.$watch('selectedUserId', (value) => {
                this.updateMedicalRecord(value);
            });
        },
        
        async updateMedicalRecord(userId) {
            if (!userId) {
                this.medicalRecordNumber = '';
                this.patientDetails = {};
                return;
            }
            
            try {
                const response = await fetch(`/api/users/${userId}/details`);
                const data = await response.json();
                
                if (data.success) {
                    this.medicalRecordNumber = data.medical_record_number || 'Belum ada';
                    this.patientDetails = data.patient_details || {};
                    
                    // Dispatch event untuk Livewire
                    this.$dispatch('patient-selected', {
                        userId: userId,
                        medicalRecordNumber: this.medicalRecordNumber,
                        details: this.patientDetails
                    });
                }
            } catch (error) {
                console.error('Error loading patient data:', error);
                this.medicalRecordNumber = 'Error loading';
            }
        }
    }
}