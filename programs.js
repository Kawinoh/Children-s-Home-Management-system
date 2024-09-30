document.addEventListener('DOMContentLoaded', function() {
    fetch('programs.json') // Path to your JSON file
        .then(response => response.json())
        .then(data => {
            const programList = document.getElementById('program-list');

            data.forEach(program => {
                // Create a new div for each program
                const programDiv = document.createElement('div');
                programDiv.classList.add('program-details');

                // Create a title element
                const title = document.createElement('h2');
                title.textContent = program.title;

                // Create a description paragraph
                const description = document.createElement('p');
                description.textContent = `Description: ${program.description}`;

                // Append title and description to the program div
                programDiv.appendChild(title);
                programDiv.appendChild(description);

                // Append the program div to the program list
                programList.appendChild(programDiv);
            });
        })
        .catch(error => console.error('Error fetching the program data:', error));
});
