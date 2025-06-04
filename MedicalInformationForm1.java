import javax.swing.*;
import java.awt.*;
import java.awt.event.*;
import java.util.ArrayList;

public class MedicalInformationForm1 extends JFrame implements ActionListener {
    // Form fields
    JTextField nameField, idField, dobField, phoneField, emergencyContactField;
    JComboBox<String> genderBox, bloodTypeBox;
    JTextArea chronicConditionsArea, allergiesArea;
    JButton submitButton, viewButton;

    // Store all submissions as list of Object arrays
    ArrayList<Object[]> submissions = new ArrayList<>();

    public MedicalInformationForm1() {
        setTitle("Medical Information Form");
        setSize(600, 650);
        setDefaultCloseOperation(EXIT_ON_CLOSE);
        setLocationRelativeTo(null);

        JPanel panel = new JPanel(new GridBagLayout());
        GridBagConstraints gbc = new GridBagConstraints();
        gbc.insets = new Insets(5, 10, 5, 10);
        gbc.fill = GridBagConstraints.HORIZONTAL;

        int y = 0;

        // Inputs
        addLabelAndField(panel, gbc, "Full Name:", nameField = new JTextField(), y++);
        addLabelAndField(panel, gbc, "Student ID:", idField = new JTextField(), y++);
        addLabelAndField(panel, gbc, "Date of Birth (YYYY-MM-DD):", dobField = new JTextField(), y++);
        addLabelAndField(panel, gbc, "Gender:", genderBox = new JComboBox<>(new String[]{"Select", "Male", "Female", "Other"}), y++);
        addLabelAndField(panel, gbc, "Phone Number:", phoneField = new JTextField(), y++);
        addLabelAndField(panel, gbc, "Emergency Contact:", emergencyContactField = new JTextField(), y++);
        addLabelAndField(panel, gbc, "Blood Type:", bloodTypeBox = new JComboBox<>(new String[]{"Select", "A+", "A-", "B+", "B-", "AB+", "AB-", "O+", "O-"}), y++);
        addLabelAndArea(panel, gbc, "Chronic Conditions:", chronicConditionsArea = new JTextArea(3, 20), y++);
        addLabelAndArea(panel, gbc, "Allergies:", allergiesArea = new JTextArea(3, 20), y++);

        // Submit Button
        submitButton = new JButton("Submit");
        submitButton.addActionListener(this);
        gbc.gridx = 0;
        gbc.gridy = y;
        panel.add(submitButton, gbc);

        // View Submissions Button
        viewButton = new JButton("View Submissions (Doctor)");
        viewButton.addActionListener(e -> showDoctorTableView());
        gbc.gridx = 1;
        panel.add(viewButton, gbc);

        add(panel);
        setVisible(true);
    }

    private void addLabelAndField(JPanel panel, GridBagConstraints gbc, String label, JComponent field, int y) {
        gbc.gridx = 0;
        gbc.gridy = y;
        panel.add(new JLabel(label), gbc);
        gbc.gridx = 1;
        panel.add(field, gbc);
    }

    private void addLabelAndArea(JPanel panel, GridBagConstraints gbc, String label, JTextArea area, int y) {
        gbc.gridx = 0;
        gbc.gridy = y;
        panel.add(new JLabel(label), gbc);
        gbc.gridx = 1;
        JScrollPane scrollPane = new JScrollPane(area);
        panel.add(scrollPane, gbc);
    }

    @Override
    public void actionPerformed(ActionEvent e) {
        // Get form data
        String name = nameField.getText().trim();
        String id = idField.getText().trim();
        String dob = dobField.getText().trim();
        String gender = (String) genderBox.getSelectedItem();
        String phone = phoneField.getText().trim();
        String emergency = emergencyContactField.getText().trim();
        String blood = (String) bloodTypeBox.getSelectedItem();
        String chronic = chronicConditionsArea.getText().trim();
        String allergies = allergiesArea.getText().trim();

        // Basic validation
        if (name.isEmpty() || id.isEmpty() || dob.isEmpty() || gender.equals("Select") ||
                phone.isEmpty() || emergency.isEmpty() || blood.equals("Select")) {
            JOptionPane.showMessageDialog(this, "Please fill out all required fields.", "Error", JOptionPane.ERROR_MESSAGE);
            return;
        }

        // Save record
        Object[] record = {name, id, dob, gender, phone, emergency, blood, chronic, allergies};
        submissions.add(record);

        JOptionPane.showMessageDialog(this, "Submission successful!");
        clearForm();
    }

    private void clearForm() {
        nameField.setText("");
        idField.setText("");
        dobField.setText("");
        genderBox.setSelectedIndex(0);
        phoneField.setText("");
        emergencyContactField.setText("");
        bloodTypeBox.setSelectedIndex(0);
        chronicConditionsArea.setText("");
        allergiesArea.setText("");
    }

    private void showDoctorTableView() {
        if (submissions.isEmpty()) {
            JOptionPane.showMessageDialog(this, "No submissions yet.");
            return;
        }

        // Table column headers
        String[] columns = {
                "Full Name", "Student ID", "DOB", "Gender", "Phone",
                "Emergency Contact", "Blood Type", "Chronic Conditions", "Allergies"
        };

        // Convert list to 2D array
        Object[][] data = submissions.toArray(new Object[0][]);

        JTable table = new JTable(data, columns);
        JScrollPane scrollPane = new JScrollPane(table);

        // Show table in a dialog
        JOptionPane.showMessageDialog(this, scrollPane, "Doctor View - Submissions", JOptionPane.INFORMATION_MESSAGE);
    }

    public static void main(String[] args) {
        SwingUtilities.invokeLater(MedicalInformationForm1::new);
    }
}