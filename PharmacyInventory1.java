import java.util.Scanner;

class Medicine {
    private String name;
    private int quantity;

    public Medicine(String name, int quantity) {
        this.name = name;
        this.quantity = quantity;
    }

    public void displayInfo() {
        System.out.println("Medicine: " + name + ", Quantity: " + quantity);
    }
}

public class PharmacyInventory1 {
    public static void main(String[] args) {
        Scanner scanner = new Scanner(System.in);

        String[] medicineNames = {"Paracetamol", "Amoxicillin", "Cough Syrup"};
        Medicine[] inventory = new Medicine[medicineNames.length];

        for (int i = 0; i < medicineNames.length; i++) {
            System.out.print("Enter quantity for " + medicineNames[i] + ": ");
            int qty = scanner.nextInt();
            inventory[i] = new Medicine(medicineNames[i], qty);
        }

        System.out.println("\n--- Pharmacy Inventory ---");
        for (Medicine med : inventory) {
            med.displayInfo();
        }

        scanner.close();
    }
}