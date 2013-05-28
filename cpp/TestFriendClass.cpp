#include<iostream>
#include "Date2.h"
#include "Date2.h"
using namespace std;

 
class AccessDate
{
	public:
		static void p()
		{
			Date birthDate;
			birthDate.year = 2000;
			cout<<birthDate.year<<endl;
		
		}


};

int main()
{
	AccessDate s1;
	s1.p();
	return 0;


}