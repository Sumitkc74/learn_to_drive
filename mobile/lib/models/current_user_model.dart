CurrentUser currentUser = CurrentUser();

class CurrentUser {
  int? id;
  String? name;
  String? email;
  String? phoneNumber;
  String? role;

  CurrentUser(
      {this.id,
      this.name,
      this.email,
      this.phoneNumber,
      this.role
    });

  factory CurrentUser.fromJson(Map<String, dynamic> json) {
    return CurrentUser(
      id : json['id'],
      name : json['name'],
      email : json['email'],
      phoneNumber : json['phoneNumber'],
      role : json['role'],
    );
  }

  Map<String, dynamic> toJson() {
    final Map<String, dynamic> data = <String, dynamic>{};
    data['id'] = id;
    data['name'] = name;
    data['email'] = email;
    data['phoneNumber'] = phoneNumber;
    data['role'] = role;
    return data;
  }
}