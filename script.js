document
  .getElementById("create-integrations")
  .addEventListener("click", function () {
    const integration = document.getElementById("integration").value;
    const selectedType = document.getElementById("type").value;

    if (!integration || !selectedType) {
      document.getElementById("result").innerHTML =
        "<p>Please Select Integration & type</p>";
      return;
    }

    fetch(
      ajaxurl +
        "?action=create_trigger&integration=" +
        integration +
        "&type=" +
        selectedType
    )
      .then((response) => response.json())
      .then((data) => {
        console.log(data);
        // const taskSelect = document.getElementById("task");
        // taskSelect.innerHTML = '<option value="">Select...</option>';
        // data.data.forEach((item) => {
        //   taskSelect.innerHTML += `<option value="${item.hook}">${item.task}</option>`;
        // });
        // taskSelect.style.display = "inline";
      });
  });

document.getElementById("integration").addEventListener("change", function () {
  const typeSelect = document.getElementById("type");

  typeSelect.style.display = this.value ? "inline" : "none";

  document.getElementById("task").style.display = "none";
  document.getElementById("task").innerHTML =
    '<option value="">Select...</option>';
  document.getElementById("result").innerHTML = "";
});

document.getElementById("type").addEventListener("change", function () {
  const integration = document.getElementById("integration").value;
  document.getElementById("task").innerHTML =
    '<option value="">Select...</option>';
  document.getElementById("task").innerHTML =
    '<option value="">Select...</option>';

  if (this.value) {
    fetch(
      ajaxurl +
        "?action=get_tasks&integration=" +
        integration +
        "&type=" +
        this.value
    )
      .then((response) => response.json())
      .then((data) => {
        const taskSelect = document.getElementById("task");
        taskSelect.innerHTML = '<option value="">Select...</option>';
        data.data.forEach((item) => {
          taskSelect.innerHTML += `<option value="${item.hook}">${item.task}</option>`;
        });
        taskSelect.style.display = "inline";
      });
  } else {
    document.getElementById("task").style.display = "none";
  }
});

document.getElementById("task").addEventListener("change", function () {
  const integration = document.getElementById("integration").value;
  const selectedType = document.getElementById("type").value;
  const resultDiv = document.getElementById("result");

  if (this.value) {
    fetch(
      ajaxurl +
        "?action=get_tasks_details&integration=" +
        integration +
        "&type=" +
        selectedType +
        "&task=" +
        this.value
    )
      .then((response) => response.json())
      .then((data) => {
        resultDiv.innerHTML = "";
        data.data.forEach((item) => {
          resultDiv.innerHTML += `<p>${item.key} : <b> ${item.value}</b></p>`;
        });
        resultDiv.style.display = "inline";
      });
  } else {
    document.getElementById("task").style.display = "none";
  }
});
