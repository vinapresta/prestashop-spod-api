
window.onload = () => {

    const spodApiBtn = document.getElementById('spodApiListBtn')

    if (spodApiBtn)
        spodApiBtn.addEventListener('click', () => {

            downloadCSV()

        })

    const categoryButtons = [...document.getElementsByClassName('categories-list__btn')]

    categoryButtons.forEach(button => {

        button.addEventListener('click', (e) => {

            const currentState = e.currentTarget.ariaPressed

            if (currentState === true)
                e.currentTarget.ariaPressed = false
            else
                e.currentTarget.ariaPressed = true

        })
        
    })

}

function downloadCSV() {

    const data = [
        ['Name', 'Age', 'Email'],
        ['John Doe', 30, 'john@example.com'],
        ['Jane Smith', 25, 'jane@example.com'],
        // Add more rows here...
    ]

    // Convert data to CSV format
    const csvContent = data.map(row => row.join(',')).join('\n');

    const blob = new Blob([csvContent], { type: 'text/csv' })

    const url = URL.createObjectURL(blob)

    const downloadLink = document.createElement('a')
    downloadLink.href = url
    downloadLink.download = 'data.csv';

    document.body.appendChild(downloadLink)

    downloadLink.click()

    document.body.removeChild(downloadLink)

    URL.revokeObjectURL(url)
}