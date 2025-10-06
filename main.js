// ====== CLOSE MODAL ======
function closeModal() {
    document.getElementById('editModal').classList.add('hidden');
}

// ====== OPEN MODAL FUNCTION ======
let packagesData = [];
let spotsData = [];
let destMap = [];

function initPackagesData(packages, spots, dests) {
    packagesData = packages;
    spotsData = spots;
    destMap = dests;
}

function openModal(id) {
    const pkg = packagesData.find(p => p.id == id);
    if (!pkg) return;

    // Fill basic fields
    document.getElementById('edit_package_id').value = pkg.id;
    document.getElementById('edit_title').value = pkg.title;
    document.getElementById('edit_price').value = pkg.price;
    document.getElementById('edit_pickup').value = pkg.pickup_location ?? '';
    document.getElementById('edit_dropoff').value = pkg.dropoff_location ?? '';
    document.getElementById('edit_description').value = pkg.description ?? '';

    // Fill inclusions/exclusions
    for (let i = 1; i <= 4; i++) {
        document.getElementById('edit_inclusion' + i).value = pkg['inclusion' + i] ?? '';
        document.getElementById('edit_exclusion' + i).value = pkg['exclusion' + i] ?? '';
    }

    // Destinations
    const destContainer = document.getElementById('edit_dest_container');
    destContainer.innerHTML = '';
    (spotsData || []).forEach(spot => {
        const checked = (destMap[id] || []).includes(spot.id.toString()) ? 'checked' : '';
        destContainer.innerHTML += `
            <label class="flex items-center space-x-2">
                <input type="checkbox" name="edit_destinations[]" value="${spot.id}" ${checked}>
                <span>${spot.name_of_tourist_spot} (${spot.location})</span>
            </label>
        `;
    });

   // Images
const imgContainer = document.getElementById('edit_images_container');
imgContainer.innerHTML = '';
for (let i = 1; i <= 4; i++) {
    let img = pkg['image' + i];
    imgContainer.innerHTML += `
        <div class="flex items-center space-x-2">
            ${img ? `<img src="uploads/${img}" class="w-20 h-20 object-cover border rounded">` : ''}
            <input type="file" name="edit_images[]">
        </div>
    `;
}


    // Show modal
    document.getElementById('editModal').classList.remove('hidden');
}


